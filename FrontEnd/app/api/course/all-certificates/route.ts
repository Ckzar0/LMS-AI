import { NextRequest, NextResponse } from "next/server"

export async function GET(request: NextRequest) {
  const moodleUrl = process.env.MOODLE_URL || "http://localhost:8080"
  const moodleToken = process.env.MOODLE_TOKEN

  if (!moodleToken) {
    return NextResponse.json({ error: "MOODLE_TOKEN not configured" }, { status: 500 })
  }

  try {
    const response = await fetch(
      `${moodleUrl}/webservice/rest/server.php?wstoken=${moodleToken}&wsfunction=local_wsmanageactivities_get_all_certificates&moodlewsrestformat=json`,
      { 
        method: 'POST',
        cache: 'no-store' 
      }
    )
    const data = await response.json()

    if (data.exception) {
      return NextResponse.json({ error: data.message }, { status: 400 })
    }

    return NextResponse.json(data)

  } catch (error) {
    return NextResponse.json({ error: "Failed to fetch certifications" }, { status: 500 })
  }
}
