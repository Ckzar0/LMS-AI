import { NextRequest, NextResponse } from "next/server"

export async function POST(request: NextRequest) {
  const { cmid, grade } = await request.json()
  const moodleUrl = process.env.MOODLE_URL || "http://localhost:8080"
  const moodleToken = process.env.MOODLE_TOKEN

  console.log(`[API QUIZ GRADE] Submitting grade for CMID ${cmid}: ${grade}`);

  if (!moodleToken) {
    console.error("[API QUIZ GRADE] MOODLE_TOKEN not configured");
    return NextResponse.json({ error: "MOODLE_TOKEN not configured" }, { status: 500 })
  }

  try {
    const url = `${moodleUrl}/webservice/rest/server.php?wstoken=${moodleToken}&wsfunction=local_wsmanageactivities_submit_quiz_grade&moodlewsrestformat=json&cmid=${cmid}&grade=${grade}`;
    console.log(`[API QUIZ GRADE] Calling Moodle: ${url.replace(moodleToken, 'HIDDEN_TOKEN')}`);

    const response = await fetch(url, { method: 'POST', cache: 'no-store' })
    const data = await response.json()
    
    console.log(`[API QUIZ GRADE] Moodle Response:`, data);
    return NextResponse.json(data)

  } catch (error) {
    console.error("[API QUIZ GRADE] Error:", error)
    return NextResponse.json({ error: "Failed to submit quiz grade" }, { status: 500 })
  }
}
