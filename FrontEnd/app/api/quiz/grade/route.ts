import { NextRequest, NextResponse } from "next/server"

export async function POST(request: NextRequest) {
  const { cmid, grade } = await request.json()
  const moodleUrl = process.env.MOODLE_URL || "http://localhost:8080"
  const moodleToken = process.env.MOODLE_TOKEN

  if (!moodleToken) {
    return NextResponse.json({ error: "MOODLE_TOKEN not configured" }, { status: 500 })
  }

  try {
    const url = `${moodleUrl}/webservice/rest/server.php?wstoken=${moodleToken}&wsfunction=local_wsmanageactivities_submit_quiz_grade&moodlewsrestformat=json&cmid=${cmid}&grade=${grade}`;

    const response = await fetch(url, { method: 'POST', cache: 'no-store' })
    const data = await response.json()
    
    return NextResponse.json(data)

  } catch (error) {
    return NextResponse.json({ error: "Failed to submit quiz grade" }, { status: 500 })
  }
}
