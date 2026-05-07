import { NextRequest, NextResponse } from "next/server"

export async function POST(request: NextRequest) {
  const { cmid, responses } = await request.json()
  
  const moodleUrl = process.env.MOODLE_URL || "http://localhost:8080"
  const moodleToken = process.env.MOODLE_TOKEN || "14c68ff68a1a57cdc4cf4d72f443b87d"

  try {
    // Format responses for Moodle WebService: responses[0][itemid]=X&responses[0][value]=Y...
    let queryParams = `wstoken=${moodleToken}&wsfunction=local_wsmanageactivities_submit_feedback_responses&moodlewsrestformat=json&cmid=${cmid}`;
    
    responses.forEach((resp: any, index: number) => {
      queryParams += `&responses[${index}][itemid]=${resp.itemid}&responses[${index}][value]=${encodeURIComponent(resp.value)}`;
    });

    const response = await fetch(
      `${moodleUrl}/webservice/rest/server.php?${queryParams}`,
      { method: 'POST', cache: 'no-store' }
    )
    
    const data = await response.json()

    if (data.exception) {
      return NextResponse.json({ error: data.message }, { status: 400 })
    }

    return NextResponse.json(data)

  } catch (error) {
    console.error("Feedback submission error:", error)
    return NextResponse.json({ error: "Failed to submit feedback" }, { status: 500 } )
  }
}
