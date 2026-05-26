import { NextRequest, NextResponse } from "next/server"

export async function POST(request: NextRequest) {
  const { courseId } = await request.json()
  const moodleUrl = process.env.MOODLE_URL || "http://localhost:8080"
  const moodleToken = process.env.MOODLE_TOKEN

  if (!moodleToken) {
    return NextResponse.json({ error: "MOODLE_TOKEN not configured" }, { status: 500 })
  }

  try {
    const response = await fetch(
      `${moodleUrl}/webservice/rest/server.php?wstoken=${moodleToken}&wsfunction=local_wsmanageactivities_enrol_user&moodlewsrestformat=json&courseid=${courseId}`,
      { method: 'POST', cache: 'no-store' }
    )
    
    const data = await response.json()
    return NextResponse.json(data)

  } catch (error) {
    return NextResponse.json({ error: "Failed to enrol user automatically" }, { status: 500 })
  }
}
