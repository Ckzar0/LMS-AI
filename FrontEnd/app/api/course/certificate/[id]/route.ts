import { NextRequest, NextResponse } from "next/server"

export async function GET(
  request: NextRequest,
  props: { params: Promise<{ id: string }> }
) {
  const params = await props.params;
  const cmid = params.id;
  const moodleUrl = process.env.MOODLE_URL || "http://localhost:8080"
  const moodleToken = process.env.MOODLE_TOKEN || "14c68ff68a1a57cdc4cf4d72f443b87d"

  try {
    const response = await fetch(
      `${moodleUrl}/webservice/rest/server.php?wstoken=${moodleToken}&wsfunction=local_wsmanageactivities_get_certificate_pdf&moodlewsrestformat=json&cmid=${cmid}`,
      { cache: 'no-store' }
    )
    
    const data = await response.json()

    if (data.exception) {
      return NextResponse.json({ error: data.message }, { status: 400 })
    }

    return NextResponse.json(data)

  } catch (error) {
    console.error("Certificate download error:", error)
    return NextResponse.json({ error: "Failed to generate certificate" }, { status: 500 })
  }
}
