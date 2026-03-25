import { NextRequest, NextResponse } from "next/server"
import type { MoodleCourse } from "@/lib/types"

export const maxDuration = 300 // 5 minutos

export async function POST(request: NextRequest) {
  try {
    const { course } = await request.json() as { course: MoodleCourse }
    
    if (!course) {
      return NextResponse.json(
        { error: "Missing course data" },
        { status: 400 }
      )
    }

    const moodleUrl = process.env.MOODLE_URL
    const moodleToken = process.env.MOODLE_TOKEN

    if (!moodleUrl || !moodleToken) {
      return NextResponse.json(
        { error: "Moodle URL or Token not configured" },
        { status: 500 }
      )
    }

    // Prepare the data for wsmanageactivities plugin
    const wsFunction = "local_wsmanageactivities_create_course_with_content"
    
    // Build the Moodle web service URL
    const wsUrl = `${moodleUrl}/webservice/rest/server.php`

    // Send to Moodle
    const formData = new URLSearchParams()
    formData.append("wstoken", moodleToken)
    formData.append("wsfunction", wsFunction)
    formData.append("moodlewsrestformat", "json")
    formData.append("coursedata", JSON.stringify(course))

    const response = await fetch(wsUrl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded"
      },
      body: formData.toString()
    })

    const data = await response.json()

    // Check for Moodle errors
    if (data.exception || data.errorcode) {
      console.error("Moodle API error:", data)
      return NextResponse.json(
        { 
          error: data.message || data.exception || "Moodle API error",
          details: data
        },
        { status: 500 }
      )
    }

    return NextResponse.json({ 
      success: true, 
      courseId: data.courseid,
      message: "Course created successfully in Moodle"
    })
  } catch (error) {
    console.error("Error sending to Moodle:", error)
    return NextResponse.json(
      { error: error instanceof Error ? error.message : "Failed to send to Moodle" },
      { status: 500 }
    )
  }
}

// Test connection to Moodle
export async function GET() {
  try {
    const moodleUrl = process.env.MOODLE_URL
    const moodleToken = process.env.MOODLE_TOKEN

    if (!moodleUrl || !moodleToken) {
      return NextResponse.json(
        { connected: false, error: "Moodle URL or Token not configured" },
        { status: 500 }
      )
    }

    // Test with a simple function
    const wsUrl = `${moodleUrl}/webservice/rest/server.php`
    const formData = new URLSearchParams()
    formData.append("wstoken", moodleToken)
    formData.append("wsfunction", "core_webservice_get_site_info")
    formData.append("moodlewsrestformat", "json")

    const response = await fetch(wsUrl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded"
      },
      body: formData.toString()
    })

    const data = await response.json()

    if (data.exception || data.errorcode) {
      return NextResponse.json(
        { connected: false, error: data.message || data.exception },
        { status: 500 }
      )
    }

    return NextResponse.json({ 
      connected: true, 
      siteName: data.sitename,
      siteUrl: data.siteurl,
      username: data.username
    })
  } catch (error) {
    return NextResponse.json(
      { connected: false, error: error instanceof Error ? error.message : "Connection failed" },
      { status: 500 }
    )
  }
}
