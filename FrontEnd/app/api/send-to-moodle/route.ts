import { NextResponse } from "next/server"

// Increase the maximum execution duration to support large PDF processing.
export const maxDuration = 300; // 5 minutes
export const dynamic = 'force-dynamic';

export async function POST(req: Request) {
  try {
    const { course, pdfFile, onlyExtract } = await req.json()
    const moodleUrl = process.env.MOODLE_URL || "http://localhost:8080"
    const moodleToken = process.env.MOODLE_TOKEN || "14c68ff68a1a57cdc4cf4d72f443b87d"
    const wsUrl = `${moodleUrl}/webservice/rest/server.php`

    let extractedFolder = "";
    let pagesWithImages: number[] = [];

    // Phase 1: PDF Image Extraction
    // If a PDF is provided, we send it to Moodle first to trigger the physical extraction 
    // of images using pdfimages and ImageMagick via the custom WebService.
    if (pdfFile && pdfFile.name) {
      const pdfFormData = new FormData();
      pdfFormData.append("wstoken", moodleToken);
      pdfFormData.append("wsfunction", "local_wsmanageactivities_process_pdf");
      pdfFormData.append("moodlewsrestformat", "json");
      pdfFormData.append("filename", pdfFile.name);
      
      // Handle large files: If the file exceeds ~15MB in base64, we do not send the content over HTTP.
      // Instead, we pass an empty string, signaling Moodle to look for the file directly
      // in the server's local /Cursos/ directory using the filename.
      if (pdfFile.content && pdfFile.content.length < 20000000) {
        pdfFormData.append("filecontent", pdfFile.content);
      } else {
        pdfFormData.append("filecontent", "");
      }

      try {
        const pdfResponse = await fetch(wsUrl, {
          method: "POST",
          body: pdfFormData
        });
        const pdfData = await pdfResponse.json();
        
        if (pdfData.status === 'success') {
          extractedFolder = pdfData.image_folder;
          pagesWithImages = pdfData.pages_with_images || [];
          // Inject the returned image folder name into the course JSON.
          // This allows Moodle's ActivityCreator to locate the extracted images during HTML generation.
          course.image_folder = extractedFolder;
        } else {
          console.warn("PDF extraction warning:", pdfData.message);
        }
      } catch (pdfErr) {
        console.error("Failed to process PDF images:", pdfErr);
      }
    }

    // Early Return for Image Pre-Extraction
    // Used by the FrontEnd to obtain image metadata before starting the AI generation process.
    if (onlyExtract) {
      return NextResponse.json({ 
        success: true, 
        image_folder: extractedFolder,
        pages_with_images: pagesWithImages,
        message: "Images extracted successfully." 
      })
    }

    // Phase 2: Course Structure Creation
    // Send the complete generated JSON structure (modules, activities, questions, config flags)
    // to Moodle to construct the physical course and its components.
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

    // Normalize the returned course ID from Moodle's WebService response.
    const finalCourseId = data.courseid || data.course_id || data.id || (typeof data === 'number' ? data : null);

    return NextResponse.json({ 
      success: true, 
      courseId: finalCourseId,
      course_shortname: course.course_shortname,
      activities: data.activities || []
    })
  } catch (error) {
    return NextResponse.json(
      { error: error instanceof Error ? error.message : "Failed to connect to Moodle" },
      { status: 500 }
    )
  }
}

export async function GET() {
  // Health Check Endpoint
  // Validates the connection and the REST API token by fetching basic site information.
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

