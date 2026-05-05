import { NextRequest, NextResponse } from "next/server"

export async function GET(
  request: NextRequest,
  props: { params: Promise<{ id: string }> }
) {
  const params = await props.params;
  const id = params.id;
  const moodleUrl = process.env.MOODLE_URL || "http://localhost:8080"
  const moodleToken = process.env.MOODLE_TOKEN

  if (!moodleToken) {
    return NextResponse.json({ error: "MOODLE_TOKEN not configured" }, { status: 500 })
  }

  try {
    // 1. Get Course Metadata
    const courseRes = await fetch(
      `${moodleUrl}/webservice/rest/server.php?wstoken=${moodleToken}&wsfunction=core_course_get_courses&moodlewsrestformat=json&options[ids][0]=${id}`,
      { cache: 'no-store' }
    )
    const courses = await courseRes.json()
    const course = Array.isArray(courses) ? courses.find((c: any) => c.id === parseInt(id)) : null

    if (!course) {
      return NextResponse.json({ error: "Course not found" }, { status: 404 })
    }

    // 2. Get Course Contents (Sections and Activities)
    const contentsRes = await fetch(
      `${moodleUrl}/webservice/rest/server.php?wstoken=${moodleToken}&wsfunction=core_course_get_contents&moodlewsrestformat=json&courseid=${id}`,
      { cache: 'no-store' }
    )
    const sections = await contentsRes.json()

    // 3. Get Enrolled Users count
    const enrolledRes = await fetch(
      `${moodleUrl}/webservice/rest/server.php?wstoken=${moodleToken}&wsfunction=core_enrol_get_enrolled_users&moodlewsrestformat=json&courseid=${id}`,
      { cache: 'no-store' }
    )
    const enrolledUsers = await enrolledRes.json()
    const enrolledCount = Array.isArray(enrolledUsers) ? enrolledUsers.length : 0

    return NextResponse.json({
      id: course.id,
      name: course.fullname,
      shortname: course.shortname,
      description: course.summary,
      sourceFile: course.idnumber || "N/A",
      createdAt: new Date(course.timecreated * 1000).toISOString().split('T')[0],
      duration: "N/A",
      enrolled: enrolledCount,
      sections: Array.isArray(sections) ? sections.map((s: any) => ({
        id: s.id,
        name: s.name,
        summary: s.summary,
        modules: s.modules.map((m: any) => ({
          id: m.id,
          name: m.name,
          modname: m.modname,
          url: m.url,
          instance: m.instance,
          description: m.description,
          completion: m.completiondata && m.completiondata.state > 0,
          locked: m.uservisible === false
        }))
      })) : []
    })

  } catch (error) {
    console.error("Course detail error:", error)
    return NextResponse.json({ error: "Failed to fetch course details" }, { status: 500 })
  }
}
