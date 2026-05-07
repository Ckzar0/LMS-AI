"use client"

import { useState, useEffect } from "react"
import { 
  ArrowLeft, 
  ChevronLeft, 
  ChevronRight, 
  CheckCircle2, 
  BookOpen, 
  FileQuestion, 
  Loader2,
  Menu,
  X,
  Lock
} from "lucide-react"
import { Button } from "@/components/ui/button"
import { Progress } from "@/components/ui/progress"
import { Badge } from "@/components/ui/badge"
import { ScrollArea } from "@/components/ui/scroll-area"
import { cn } from "@/lib/utils"

import { QuizEngine } from "@/components/lms/quiz-engine"
import { FeedbackEngine } from "@/components/lms/feedback-engine"

interface LearningViewerProps {
  courseId: string
  initialActivityId: string
  onBack: () => void
}

export function LearningViewer({ courseId, initialActivityId, onBack }: LearningViewerProps) {
  const [course, setCourse] = useState<any>(null)
  const [currentActivity, setCurrentActivity] = useState<any>(null)
  const [quizData, setQuizData] = useState<any>(null)
  const [feedbackData, setFeedbackData] = useState<any>(null)
  const [loading, setLoading] = useState(true)
  const [loadingContent, setLoadingContent] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [sidebarOpen, setSidebarOpen] = useState(true)

  // 0. Auto-Enrol user to ensure progress tracking works
  useEffect(() => {
    const autoEnrol = async () => {
      try {
        await fetch('/api/course/enrol', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ courseId })
        });
      } catch (err) {
        console.error("Auto enrolment failed:", err);
      }
    }
    autoEnrol();
  }, [courseId])

  // 1. Fetch Course Structure (Index)
  const fetchCourse = async () => {
    try {
      const response = await fetch(`/api/course/${courseId}`)
      if (!response.ok) throw new Error("Falha ao carregar estrutura do curso")
      const data = await response.json()
      setCourse(data)
    } catch (err) {
      setError(err instanceof Error ? err.message : "Erro desconhecido")
    }
  }

  useEffect(() => {
    fetchCourse()
  }, [courseId])

  // 2. Fetch Activity Content & Mark Viewed
  const loadActivity = async (id: string) => {
    setLoadingContent(true)
    setQuizData(null) // Reset quiz data
    setFeedbackData(null) // Reset feedback data
    try {
      const response = await fetch(`/api/activity/${id}`)
      if (!response.ok) throw new Error("Falha ao carregar conteúdo")
      const data = await response.json()
      console.log(`[VIEWER] Loaded activity ${id}, type: ${data.type}`);
      setCurrentActivity(data)
      
      // If it's a quiz, fetch full quiz data
      if (data.type === 'quiz') {
        const quizRes = await fetch(`/api/quiz/${id}`)
        const qData = await quizRes.json()
        setQuizData(qData)
      } else if (data.type === 'feedback') {
        console.log(`[VIEWER] Fetching feedback data for CMID ${id}...`);
        const feedbackRes = await fetch(`/api/feedback/${id}?t=${Date.now()}`)
        const fData = await feedbackRes.json()
        console.log(`[VIEWER] Feedback data received:`, fData);
        setFeedbackData(fData)
      }

      // Mark as viewed in Moodle
      const markRes = await fetch('/api/activity/mark-viewed', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ cmid: id })
      })
      
      if (markRes.ok) {
        setTimeout(fetchCourse, 1000)
      }

    } catch (err) {
      console.error("Error loading activity:", err)
    } finally {
      setLoadingContent(false)
      setLoading(false)
    }
  }

  useEffect(() => {
    if (initialActivityId) {
      loadActivity(initialActivityId)
    }
  }, [initialActivityId])

  const allActivities = course?.sections.flatMap((s: any) => s.modules) || []
  const currentIndex = allActivities.findIndex((a: any) => a.id.toString() === currentActivity?.id.toString())
  
  const handleNavigate = (direction: 'prev' | 'next') => {
    const nextIndex = direction === 'next' ? currentIndex + 1 : currentIndex - 1
    if (nextIndex >= 0 && nextIndex < allActivities.length) {
      const nextActivity = allActivities[nextIndex]
      loadActivity(nextActivity.id.toString())
      // Scroll to top of the content area
      const contentArea = document.getElementById('learning-content-area')
      if (contentArea) contentArea.scrollTo(0, 0)
    }
  }

  if (loading && !course) {
    return (
      <div className="flex flex-col items-center justify-center h-screen gap-4">
        <Loader2 className="h-10 w-10 animate-spin text-primary" />
        <p className="text-muted-foreground font-medium">A entrar na sala de aula...</p>
      </div>
    )
  }

  const progress = course ? Math.round((allActivities.filter((a: any) => a.completion).length / allActivities.length) * 100) : 0

  return (
    <div className="flex h-full min-h-[600px] overflow-hidden bg-background border rounded-xl shadow-inner">
      {/* Sidebar - Course Index */}
      <div className={cn(
        "bg-muted/30 border-r transition-all duration-300 flex flex-col h-full",
        sidebarOpen ? "w-80" : "w-0 overflow-hidden border-none"
      )}>
        <div className="p-4 border-b bg-white/50 shrink-0">
          <div className="flex items-center justify-between mb-2">
             <h2 className="font-bold text-sm uppercase tracking-wider text-muted-foreground">Índice do Curso</h2>
             <Button variant="ghost" size="icon" onClick={() => setSidebarOpen(false)} className="h-8 w-8">
               <X className="h-4 w-4" />
             </Button>
          </div>
          <div className="space-y-1">
            <div className="flex justify-between text-xs mb-1">
              <span className="font-medium">O teu progresso</span>
              <span>{progress}%</span>
            </div>
            <Progress value={progress} className="h-1.5" />
          </div>
        </div>

        <ScrollArea className="flex-1 overflow-y-auto">
          <div className="p-2 pb-8 space-y-4">
            {course?.sections.map((section: any, sIdx: number) => {
               if (section.modules.length === 0 && section.name === "General") return null;
               
               return (
                <div key={section.id} className="space-y-1">
                  <h3 className="px-3 py-2 text-xs font-bold text-primary/70 uppercase">
                    {sIdx + 1}. {section.name}
                  </h3>
                  <div className="space-y-0.5">
                    {section.modules.map((module: any) => (
                      <button
                        key={module.id}
                        onClick={() => {
                          if (!module.locked && module.id.toString() !== currentActivity?.id?.toString()) {
                            loadActivity(module.id.toString())
                          }
                        }}
                        disabled={module.locked}
                        className={cn(
                          "w-full flex items-start gap-3 p-3 rounded-lg text-left transition-colors",
                          currentActivity?.id?.toString() === module.id.toString() 
                            ? "bg-primary/10 text-primary font-medium" 
                            : module.locked 
                              ? "opacity-50 cursor-not-allowed"
                              : "hover:bg-muted text-muted-foreground"
                        )}
                      >
                        <div className="mt-0.5 shrink-0">
                          {module.locked ? (
                            <Lock className="h-4 w-4 text-muted-foreground" />
                          ) : module.completion ? (
                            <CheckCircle2 className="h-4 w-4 text-green-500" />
                          ) : module.modname === "page" ? (
                            <BookOpen className="h-4 w-4" />
                          ) : (
                            <FileQuestion className="h-4 w-4" />
                          )}
                        </div>
                        <span className="text-sm line-clamp-2">{module.name}</span>
                      </button>
                    ))}
                  </div>
                </div>
               )
            })}
          </div>
        </ScrollArea>
      </div>

      {/* Main Content Area */}
      <div className="flex-1 flex flex-col min-w-0 bg-white h-full">
        {/* Sub-Header */}
        <div className="h-16 border-b flex items-center justify-between px-6 bg-white/80 backdrop-blur sticky top-0 z-10 shrink-0">
          <div className="flex items-center gap-4 min-w-0">
            {!sidebarOpen && (
              <Button variant="ghost" size="icon" onClick={() => setSidebarOpen(true)}>
                <Menu className="h-5 w-5" />
              </Button>
            )}
            <div className="min-w-0">
               <h1 className="font-bold truncate text-foreground">
                 {loadingContent ? "A carregar..." : currentActivity?.name}
               </h1>
               <div className="flex items-center gap-2">
                 <Badge variant="secondary" className="text-[10px] h-4">
                   {currentActivity?.type === 'page' ? 'Conteúdo' : 'Avaliação'}
                 </Badge>
                 <span className="text-[10px] text-muted-foreground">
                   Atividade {currentIndex + 1} de {allActivities.length}
                 </span>
               </div>
            </div>
          </div>
          
          <div className="flex items-center gap-2">
            <Button variant="outline" size="sm" onClick={onBack} className="gap-2 font-bold">
              <ArrowLeft className="h-4 w-4" /> Sair
            </Button>
          </div>
        </div>

        {/* Content Area - Using native scroll for better reliability in complex layouts */}
        <div id="learning-content-area" className="flex-1 overflow-y-auto">
          <div className="max-w-4xl mx-auto p-8 md:p-12 min-h-full flex flex-col">
            <div className="flex-1">
              {loadingContent ? (
                <div className="flex flex-col items-center justify-center py-20 gap-4">
                  <Loader2 className="h-8 w-8 animate-spin text-primary opacity-50" />
                  <p className="text-muted-foreground">A processar conteúdo do Moodle...</p>
                </div>
              ) : currentActivity?.type === 'feedback' && feedbackData ? (
                <div className="py-8">
                  <FeedbackEngine 
                    cmid={currentActivity.id}
                    feedbackData={feedbackData} 
                    onComplete={async (average, responses) => {
                      // 1. Mark as viewed/complete in Moodle
                      await fetch('/api/activity/mark-viewed', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ cmid: currentActivity.id })
                      });
                      
                      // 2. Update sidebar status
                      setTimeout(fetchCourse, 2000);
                    }}
                  />
                </div>
              ) : currentActivity?.type === 'quiz' && quizData ? (
                <div className="py-8">
                  <QuizEngine 
                    quizData={quizData} 
                    onComplete={async (passed, score) => {
                      console.log(`[QUIZ] Completed. Passed: ${passed}, Score: ${score}`);
                      if (passed) {
                        console.log(`[QUIZ] Submitting grade to Moodle for CMID ${currentActivity.id}...`);
                        try {
                          const res = await fetch('/api/quiz/grade', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ 
                              cmid: currentActivity.id,
                              grade: score 
                            })
                          });
                          const result = await res.json();
                          console.log(`[QUIZ] Moodle Grade API Result:`, result);
                        } catch (err) {
                          console.error(`[QUIZ] Failed to submit grade:`, err);
                        }
                      }
                      console.log(`[QUIZ] Scheduling sidebar refresh in 3.5s...`);
                      setTimeout(fetchCourse, 3500); 
                    }} 


                    onReview={() => {
                      // Go to the first activity
                      if (allActivities.length > 0) {
                        loadActivity(allActivities[0].id.toString());
                      }
                    }}
                    onFinish={() => handleNavigate('next')}
                  />
                </div>
              ) : (
                <div className="prose prose-slate max-w-none prose-headings:text-primary prose-a:text-primary mb-12">
                  <div 
                    className="ailms-content-wrapper"
                    dangerouslySetInnerHTML={{ __html: currentActivity?.content }} 
                  />
                </div>
              )}
            </div>

            {/* Navigation Footer - Always at the bottom of content */}
            {!loadingContent && currentActivity?.type !== 'quiz' && (
              <div className="mt-auto pt-8 border-t flex items-center justify-between bg-white pb-4 shrink-0">
                <Button 
                  variant="outline" 
                  onClick={() => handleNavigate('prev')}
                  disabled={currentIndex <= 0}
                  className="gap-2"
                >
                  <ChevronLeft className="h-4 w-4" /> Anterior
                </Button>
                
                <div className="text-sm font-medium text-muted-foreground">
                  {currentIndex + 1} / {allActivities.length}
                </div>

                <Button 
                  onClick={() => handleNavigate('next')}
                  disabled={currentIndex >= allActivities.length - 1}
                  className="gap-2 font-bold shadow-md"
                >
                  Próximo <ChevronRight className="h-4 w-4" />
                </Button>
              </div>
            )}
          </div>
        </div>
      </div>
      
      <style jsx global>{`
        /* --- Base Content Layout --- */
        .ailms-content-wrapper {
          font-family: var(--font-sans), system-ui, sans-serif;
          color: hsl(var(--foreground));
          line-height: 1.8;
          max-width: 900px;
          margin: 0 auto;
        }
        
        .ailms-content-wrapper h2 {
          color: hsl(var(--primary));
          font-weight: 900;
          font-size: 2.5rem;
          letter-spacing: -0.04em;
          margin-bottom: 2rem;
          line-height: 1.1;
        }

        .ailms-content-wrapper p {
          font-size: 1.15rem;
          margin-bottom: 1.5rem;
          color: hsl(var(--foreground) / 0.85);
        }

        /* --- MODERN CARD SYSTEM --- */
        .ailms-info-box, 
        .ailms-deep-dive, 
        .ailms-case-study, 
        .ailms-further-reading {
          margin: 3rem 0;
          padding: 2rem;
          border-radius: 1.5rem;
          border: 1px solid transparent;
          box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
          position: relative;
          transition: transform 0.2s ease;
        }

        .ailms-info-box:hover, .ailms-deep-dive:hover {
          transform: translateY(-2px);
        }

        .ailms-info-box h3, 
        .ailms-deep-dive h3, 
        .ailms-case-study h3, 
        .ailms-further-reading h3 {
          margin-top: 0;
          font-size: 1.35rem;
          font-weight: 800;
          display: flex;
          align-items: center;
          gap: 0.75rem;
          margin-bottom: 1rem;
        }

        /* Info Card - Blue Theme */
        .ailms-info-box {
          background: linear-gradient(135deg, #f0f7ff 0%, #ffffff 100%);
          border-color: #bae6fd;
          border-left: 8px solid #0284c7;
        }
        .ailms-info-box h3 { color: #0369a1; }
        .ailms-info-box h3::before { content: "💡"; font-size: 1.5rem; }

        /* Deep Dive - Amber/Gold Theme */
        .ailms-deep-dive {
          background: linear-gradient(135deg, #fffbeb 0%, #ffffff 100%);
          border-color: #fef3c7;
          border-left: 8px solid #d97706;
        }
        .ailms-deep-dive h3 { color: #92400e; }
        .ailms-deep-dive h3::before { content: "🚀"; font-size: 1.5rem; }

        /* Case Study - Green Theme */
        .ailms-case-study {
          background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%);
          border-color: #dcfce7;
          border-left: 8px solid #16a34a;
        }
        .ailms-case-study h3 { color: #166534; }
        .ailms-case-study h3::before { content: "📂"; font-size: 1.5rem; }

        /* Further Reading - Purple Theme */
        .ailms-further-reading {
          background: linear-gradient(135deg, #faf5ff 0%, #ffffff 100%);
          border-color: #f3e8ff;
          border-left: 8px solid #9333ea;
        }
        .ailms-further-reading h3 { color: #6b21a8; }
        .ailms-further-reading h3::before { content: "🔗"; font-size: 1.5rem; }

        /* Quick Check - Interactive Card */
        .ailms-quick-check {
          background: #1e293b;
          color: #f8fafc;
          padding: 2rem;
          border-radius: 1.5rem;
          margin: 4rem 0;
          text-align: center;
          border: 4px solid #334155;
          box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        .ailms-quick-check strong {
          display: block;
          color: #38bdf8;
          font-size: 1.4rem;
          margin-bottom: 0.75rem;
          text-transform: uppercase;
          letter-spacing: 0.1em;
        }

        /* --- Images --- */
        .ailms-figure {
          margin: 4rem 0;
          background: #f8fafc;
          padding: 1.5rem;
          border-radius: 2rem;
          border: 1px solid #e2e8f0;
          display: flex;
          flex-direction: column;
          align-items: center;
          text-align: center;
        }
        
        .ailms-figure img {
          display: block;
          margin-left: auto;
          margin-right: auto;
          border-radius: 1rem;
          box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
          max-width: 100%;
          height: auto;
        }

        .ailms-img-caption {
          margin-top: 1.5rem;
          font-weight: 700;
          color: #64748b;
          text-transform: uppercase;
          font-size: 0.85rem;
          letter-spacing: 0.05em;
          max-width: 80%;
          text-align: center;
          display: inline-block;
        }

        /* --- Lists --- */
        .ailms-content-wrapper ul {
          list-style: none;
          padding-left: 0;
        }
        .ailms-content-wrapper ul li {
          position: relative;
          padding-left: 2rem;
          margin-bottom: 1rem;
        }
        .ailms-content-wrapper ul li::before {
          content: "→";
          position: absolute;
          left: 0;
          color: hsl(var(--primary));
          font-weight: bold;
        }

        /* --- MODERN TABLES --- */
        .ailms-table-container {
          margin: 3rem 0;
          border-radius: 1.25rem;
          border: 1px solid #e2e8f0;
          overflow: hidden;
          box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
          background: white;
        }

        .ailms-table-wrapper {
          overflow-x: auto;
          width: 100%;
        }

        .ailms-table {
          width: 100%;
          border-collapse: collapse;
          font-size: 1rem;
          text-align: left;
          min-width: 600px;
        }
        
        .ailms-table th {
          background-color: #f8fafc;
          padding: 1.25rem 1.5rem;
          font-weight: 800;
          color: #1e293b;
          border-bottom: 2px solid #e2e8f0;
          text-transform: uppercase;
          font-size: 0.85rem;
          letter-spacing: 0.05em;
        }
        
        .ailms-table td {
          padding: 1.25rem 1.5rem;
          border-bottom: 1px solid #f1f5f9;
          color: #475569;
          line-height: 1.5;
        }
        
        .ailms-table tr:last-child td {
          border-bottom: none;
        }

        .ailms-table tr:nth-child(even) {
          background-color: #f8fafc;
        }

        .ailms-table tr:hover td {
          background-color: #f1f5f9;
          color: hsl(var(--primary));
        }

        /* Table Tip */
        .ailms-table-container::after {
          content: "↔ Deslize para ver mais colunas";
          display: block;
          text-align: right;
          padding: 0.5rem 1.5rem;
          font-size: 0.7rem;
          color: #94a3b8;
          font-weight: bold;
          text-transform: uppercase;
          background: #f8fafc;
          border-top: 1px solid #e2e8f0;
        }
      `}</style>
    </div>
  )
}
e>
    </div>
  )
}
