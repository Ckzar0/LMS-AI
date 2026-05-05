"use client"

import { useState } from "react"
import { Sidebar } from "@/components/lms/sidebar"
import { Header } from "@/components/lms/header"
import { Dashboard } from "@/components/lms/dashboard"
import { CoursesView } from "@/components/lms/courses-view"
import { CourseDetail } from "@/components/lms/course-detail"
import { UploadView } from "@/components/lms/upload-view"
import { CertificationsView } from "@/components/lms/certifications-view"
import { UsersView } from "@/components/lms/users-view"
import { LearningViewer } from "@/components/lms/learning-viewer"

export type ViewType = "dashboard" | "courses" | "course-detail" | "upload" | "certifications" | "users" | "learning-viewer"

export default function LMSPage() {
  const [currentView, setCurrentView] = useState<ViewType>("dashboard")
  const [selectedCourseId, setSelectedCourseId] = useState<string | null>(null)
  const [selectedActivityId, setSelectedActivityId] = useState<string | null>(null)

  const handleCourseSelect = (courseId: string) => {
    setSelectedCourseId(courseId)
    setCurrentView("course-detail")
  }

  const handleActivitySelect = (courseId: string, activityId: string) => {
    setSelectedCourseId(courseId)
    setSelectedActivityId(activityId)
    setCurrentView("learning-viewer")
  }

  const renderView = () => {
    switch (currentView) {
      case "dashboard":
        return <Dashboard onCourseSelect={handleCourseSelect} />
      case "courses":
        return <CoursesView onCourseSelect={handleCourseSelect} />
      case "course-detail":
        return (
          <CourseDetail 
            courseId={selectedCourseId} 
            onBack={() => setCurrentView("courses")} 
            onStartActivity={(activityId) => handleActivitySelect(selectedCourseId!, activityId)}
          />
        )
      case "learning-viewer":
        return (
          <LearningViewer 
            courseId={selectedCourseId!} 
            initialActivityId={selectedActivityId!} 
            onBack={() => setCurrentView("course-detail")} 
          />
        )
      case "upload":
        return <UploadView />
      case "certifications":
        return <CertificationsView />
      case "users":
        return <UsersView />
      default:
        return <Dashboard onCourseSelect={handleCourseSelect} />
    }
  }

  return (
    <div className="flex h-screen bg-background">
      <Sidebar currentView={currentView} onViewChange={setCurrentView} />
      <div className="flex-1 flex flex-col overflow-hidden">
        <Header />
        <main className="flex-1 overflow-auto p-6">
          {renderView()}
        </main>
      </div>
    </div>
  )
}
