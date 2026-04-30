import { NextResponse } from "next/server"

// Aumentar o limite para suportar PDFs grandes
export const maxDuration = 300; // 5 minutos
export const dynamic = 'force-dynamic';

export async function POST(req: Request) {
  try {
    const { course, pdfFile, onlyExtract } = await req.json()
    const moodleUrl = process.env.MOODLE_URL || "http://localhost:8080"
    const moodleToken = process.env.MOODLE_TOKEN || "14c68ff68a1a57cdc4cf4d72f443b87d"
    const wsUrl = `${moodleUrl}/webservice/rest/server.php`

    let extractedFolder = "";

    // 1. If PDF is provided, send it first to extract images
    if (pdfFile && pdfFile.content) {
      const pdfFormData = new FormData();
      pdfFormData.append("wstoken", moodleToken);
      pdfFormData.append("wsfunction", "local_wsmanageactivities_process_pdf");
      pdfFormData.append("moodlewsrestformat", "json");
      pdfFormData.append("filename", pdfFile.name);
      pdfFormData.append("filecontent", pdfFile.content);

      try {
        const pdfResponse = await fetch(wsUrl, {
          method: "POST",
          body: pdfFormData
        });
        const pdfData = await pdfResponse.json();
        
        if (pdfData.status === 'success') {
          extractedFolder = pdfData.image_folder;
          // Inject the image folder into the course data so ActivityCreator knows where to look
          course.image_folder = extractedFolder;
        } else {
          console.warn("PDF extraction warning:", pdfData.message);
        }
      } catch (pdfErr) {
        console.error("Failed to process PDF images:", pdfErr);
      }
    }

    // Se o pedido for apenas para extrair imagens, paramos aqui
    if (onlyExtract) {
      return NextResponse.json({ 
        success: true, 
        image_folder: extractedFolder,
        message: "Imagens extraídas com sucesso" 
      })
    }

    // 2. Create the course structure in Moodle
    const formData = new FormData()
    formData.append("wstoken", moodleToken)
    formData.append("wsfunction", "local_wsmanageactivities_create_course_with_content")
    formData.append("moodlewsrestformat", "json")
    formData.append("coursedata", JSON.stringify(course))

    const response = await fetch(wsUrl, {
      method: "POST",
      body: formData
    })

    const data = await response.json()

    if (data.exception) {
      return NextResponse.json({ error: data.message }, { status: 400 })
    }

    // Se o Moodle devolveu um ID, garantir que ele vai para o FrontEnd como courseId
    // O Moodle WebService devolve 'courseid' segundo execute_returns()
    const finalCourseId = data.courseid || data.course_id || data.id || (typeof data === 'number' ? data : null);

    return NextResponse.json({ 
      success: true, 
      courseId: finalCourseId,
      course_shortname: course.course_shortname,
      activities: data.activities || []
    })
  } catch (error) {
    console.error("Error in send-to-moodle:", error)
    return NextResponse.json(
      { error: error instanceof Error ? error.message : "Failed to connect to Moodle" },
      { status: 500 }
    )
  }
}

export async function GET() {
  try {
    const moodleUrl = process.env.MOODLE_URL || "http://localhost:8080"
    const moodleToken = process.env.MOODLE_TOKEN || "14c68ff68a1a57cdc4cf4d72f443b87d"
    const wsUrl = `${moodleUrl}/webservice/rest/server.php`

    const formData = new FormData()
    formData.append("wstoken", moodleToken)
    formData.append("wsfunction", "core_webservice_get_site_info")
    formData.append("moodlewsrestformat", "json")

    const response = await fetch(wsUrl, {
      method: "POST",
      body: formData
    })

    const data = await response.json()
    
    if (data.exception) {
      throw new Error(data.message)
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
