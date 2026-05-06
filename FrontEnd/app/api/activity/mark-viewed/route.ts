import { NextRequest, NextResponse } from "next/server"

export async function POST(request: NextRequest) {
  const { cmid } = await request.json()
  const moodleUrl = process.env.MOODLE_URL || "http://localhost:8080"
  const moodleToken = process.env.MOODLE_TOKEN

  if (!moodleToken) {
    return NextResponse.json({ error: "MOODLE_TOKEN not configured" }, { status: 500 })
  }

  try {
    const response = await fetch(
      `${moodleUrl}/webservice/rest/server.php?wstoken=${moodleToken}&wsfunction=local_wsmanageactivities_mark_activity_viewed&moodlewsrestformat=json&cmid=${cmid}`,
      { method: 'POST', cache: 'no-store' }
    )
    
    const data = await response.json()
    return NextResponse.json(data)

  } catch (error) {
    console.error("Mark viewed error:", error)
    return NextResponse.json({ error: "Failed to mark activity as viewed" }, { status: 500 })
  }
}
