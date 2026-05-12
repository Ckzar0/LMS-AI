import { NextRequest, NextResponse } from "next/server"

export async function GET(request: NextRequest) {
  const moodleUrl = process.env.MOODLE_URL || "http://localhost:8080"
  const moodleToken = process.env.MOODLE_TOKEN

  if (!moodleToken) {
    return NextResponse.json({ error: "MOODLE_TOKEN not configured" }, { status: 500 })
  }

  try {
    // 1. Get Aggregate Stats from our new Moodle WebService
    let aggregateStats = {
      total_courses: 0,
      total_users: 0,
      total_certificates: 0,
      global_rating: 0,
      weekly_activity: [
        { day: "Mon", count: 0 },
        { day: "Tue", count: 0 },
        { day: "Wed", count: 0 },
        { day: "Thu", count: 0 },
        { day: "Fri", count: 0 },
        { day: "Sat", count: 0 },
        { day: "Sun", count: 0 }
      ]
    };

    try {
      const statsRes = await fetch(
        `${moodleUrl}/webservice/rest/server.php?wstoken=${moodleToken}&wsfunction=local_wsmanageactivities_get_dashboard_stats&moodlewsrestformat=json`,
        { method: 'POST', cache: 'no-store' }
      );
      const statsData = await statsRes.json();
      if (!statsData.exception) {
        aggregateStats = statsData;
      }
    } catch (e) {
      console.error("Error fetching aggregate stats:", e);
    }

    // 2. Get Courses list
    const coursesRes = await fetch(
      `${moodleUrl}/webservice/rest/server.php?wstoken=${moodleToken}&wsfunction=core_course_get_courses&moodlewsrestformat=json`,
      { method: 'POST', cache: 'no-store' }
    )
    const courses = await coursesRes.json()
    const realCourses = Array.isArray(courses) ? courses.filter((c: any) => c.id !== 1) : []
    realCourses.sort((a, b) => (b.timecreated || 0) - (a.timecreated || 0))

    // 3. Calculate Real Progress for top courses
    const topCourses = realCourses.slice(0, 10);
    const coursesWithProgress = await Promise.all(topCourses.map(async (course: any) => {
      try {
        const contentsRes = await fetch(
          `${moodleUrl}/webservice/rest/server.php?wstoken=${moodleToken}&wsfunction=core_course_get_contents&moodlewsrestformat=json&courseid=${course.id}`,
          { method: 'POST', cache: 'no-store' }
        );
        const sections = await contentsRes.json();
        
        let progress = 0;
        if (Array.isArray(sections)) {
          const allModules = sections.flatMap((s: any) => s.modules);
          const modulesWithCompletion = allModules.filter((m: any) => m.completion !== 0);
          
          if (modulesWithCompletion.length > 0) {
            const completedModules = modulesWithCompletion.filter((m: any) => 
              m.completiondata && m.completiondata.state > 0
            );
            progress = Math.round((completedModules.length / modulesWithCompletion.length) * 100);
          } else {
            progress = 100;
          }
        }

        // Fetch Individual Rating
        let rating = 0;
        try {
          const ratingRes = await fetch(
            `${moodleUrl}/webservice/rest/server.php?wstoken=${moodleToken}&wsfunction=local_wsmanageactivities_get_course_rating&moodlewsrestformat=json&courseid=${course.id}`,
            { method: 'POST', cache: 'no-store' }
          );
          const ratingData = await ratingRes.json();
          rating = ratingData.rating || 0;
        } catch (e) { }

        return {
          id: course.id,
          name: course.fullname,
          shortname: course.shortname,
          timecreated: course.timecreated,
          progress,
          rating,
          status: "published"
        };
      } catch (e) {
        return {
          id: course.id,
          name: course.fullname,
          shortname: course.shortname,
          timecreated: course.timecreated,
          progress: 0,
          rating: 0,
          status: "published"
        };
      }
    }));

    return NextResponse.json({
      totalCourses: aggregateStats.total_courses || realCourses.length,
      totalUsers: aggregateStats.total_users || 0,
      totalCertifications: aggregateStats.total_certificates || 0,
      averageRating: aggregateStats.global_rating || 0,
      weeklyActivity: aggregateStats.weekly_activity,
      recentCourses: coursesWithProgress
    })

  } catch (error) {
    console.error("Dashboard stats error:", error)
    return NextResponse.json({ error: "Failed to fetch dashboard stats" }, { status: 500 })
  }
}
