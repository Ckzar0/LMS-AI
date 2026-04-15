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
      `${moodleUrl}/webservice/rest/server.php?wstoken=${moodleToken}&wsfunction=core_course_get_courses&moodlewsrestformat=json`
    )
    const courses = await coursesRes.json()
    
    // Filter out course with ID 1 (Front page)
    const realCourses = Array.isArray(courses) ? courses.filter((c: any) => c.id !== 1) : []

    // 2. Get Users
    const usersRes = await fetch(
      `${moodleUrl}/webservice/rest/server.php?wstoken=${moodleToken}&wsfunction=core_user_get_users&moodlewsrestformat=json&criteria[0][key]=username&criteria[0][value]=%`
    )
    const usersData = await usersRes.json()
    const users = usersData.users || []

    // 3. Mock some data for the ones not easily available via WS
    // In a real scenario, you'd calculate this from completions or other tables
    const totalCertifications = realCourses.length * 5 // Mock
    const averageRating = 4.5 // Mock

    return NextResponse.json({
      totalCourses: realCourses.length,
      totalUsers: users.length,
      totalCertifications,
      averageRating,
      recentCourses: realCourses.slice(0, 5).map((c: any) => ({
        id: c.id,
        name: c.fullname,
        shortname: c.shortname,
        timecreated: c.timecreated,
        enrolled: 0, // In Moodle you'd need core_enrol_get_enrolled_users per course
        progress: 100,
        status: "published"
      }))
    })

  } catch (error) {
    console.error("Dashboard stats error:", error)
    return NextResponse.json({ error: "Failed to fetch dashboard stats" }, { status: 500 })
  }
}
