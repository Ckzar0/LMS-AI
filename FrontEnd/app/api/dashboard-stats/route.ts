import { NextRequest, NextResponse } from "next/server"

export async function GET(request: NextRequest) {
  const moodleUrl = process.env.MOODLE_URL || "http://localhost:8080"
  const moodleToken = process.env.MOODLE_TOKEN

  if (!moodleToken) {
    return NextResponse.json({ error: "MOODLE_TOKEN not configured" }, { status: 500 })
  }

  try {
    // 1. Get Courses
    const coursesRes = await fetch(
      `${moodleUrl}/webservice/rest/server.php?wstoken=${moodleToken}&wsfunction=core_course_get_courses&moodlewsrestformat=json`,
      { 
        method: 'POST',
        cache: 'no-store' 
      }
    )
    const courses = await coursesRes.json()
    
    // Filter out course with ID 1 (Front page)
    const realCourses = Array.isArray(courses) ? courses.filter((c: any) => c.id !== 1) : []

    // Sort by timecreated DESC (newest first)
    realCourses.sort((a, b) => (b.timecreated || 0) - (a.timecreated || 0))

    // 2. Get Users
    const usersRes = await fetch(
      `${moodleUrl}/webservice/rest/server.php?wstoken=${moodleToken}&wsfunction=core_user_get_users&moodlewsrestformat=json&criteria[0][key]=username&criteria[0][value]=%`,
      { 
        method: 'POST',
        cache: 'no-store' 
      }
    )
    const usersData = await usersRes.json()
    const users = usersData.users || []

    // 3. Calculate Real Progress for each course
    // To be efficient, we only do this for recent courses (e.g., top 10)
    const topCourses = realCourses.slice(0, 10);
    
    const coursesWithProgress = await Promise.all(topCourses.map(async (course: any) => {
      try {
        const contentsRes = await fetch(
          `${moodleUrl}/webservice/rest/server.php?wstoken=${moodleToken}&wsfunction=core_course_get_contents&moodlewsrestformat=json&courseid=${course.id}`,
          { 
            method: 'POST',
            cache: 'no-store' 
          }
        );
        const sections = await contentsRes.json();
        
        if (!Array.isArray(sections)) return { ...course, progress: 0 };

        const allModules = sections.flatMap((s: any) => s.modules);
        const modulesWithCompletion = allModules.filter((m: any) => m.completion !== 0);
        
        if (modulesWithCompletion.length === 0) {
           return { ...course, progress: 100 }; // If no completion rules, consider 100% or 0%? Let's say 100%
        }

        const completedModules = modulesWithCompletion.filter((m: any) => 
          m.completiondata && m.completiondata.state > 0
        );

        const progress = Math.round((completedModules.length / modulesWithCompletion.length) * 100);

        return {
          id: course.id,
          name: course.fullname,
          shortname: course.shortname,
          timecreated: course.timecreated,
          enrolled: 0, // Placeholder
          progress: progress,
          status: "published"
        };
      } catch (e) {
        return {
          id: course.id,
          name: course.fullname,
          shortname: course.shortname,
          timecreated: course.timecreated,
          enrolled: 0,
          progress: 0,
          status: "published"
        };
      }
    }));

    // 4. Totals
    const totalCertifications = realCourses.length * 5 // Mock
    const averageRating = 4.5 // Mock

    return NextResponse.json({
      totalCourses: realCourses.length,
      totalUsers: users.length,
      totalCertifications,
      averageRating,
      recentCourses: coursesWithProgress
    })

  } catch (error) {
    console.error("Dashboard stats error:", error)
    return NextResponse.json({ error: "Failed to fetch dashboard stats" }, { status: 500 })
  }
}
