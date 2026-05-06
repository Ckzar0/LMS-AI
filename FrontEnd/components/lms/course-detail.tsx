"use client"

import { useState, useEffect } from "react"
import { 
  ArrowLeft, 
  Users, 
  Clock, 
  FileText, 
  CheckCircle2, 
  Award,
  BarChart3,
  FileQuestion,
  BookOpen,
  MessageSquare,
  Loader2,
  Lock
} from "lucide-react"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Progress } from "@/components/ui/progress"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { Badge } from "@/components/ui/badge"
import { cn } from "@/lib/utils"

interface CourseDetailProps {
  courseId: string | null
  onBack: () => void
  onStartActivity: (activityId: string) => void
}

export function CourseDetail({ courseId, onBack, onStartActivity }: CourseDetailProps) {
  const [activeTab, setActiveTab] = useState("content")
  const [expandedModule, setExpandedModule] = useState<number | null>(null)
  const [course, setCourse] = useState<any>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    async function fetchCourseDetails() {
      if (!courseId) return
      setLoading(true)
      try {
        const response = await fetch(`/api/course/${courseId}`)
        if (!response.ok) throw new Error("Falha ao carregar detalhes do curso")
        const data = await response.json()
        setCourse(data)
        if (data.sections && data.sections.length > 0) {
          // Find first section with modules to expand
          const firstSectionWithModules = data.sections.find((s: any) => s.modules.length > 0)
          if (firstSectionWithModules) {
            setExpandedModule(firstSectionWithModules.id)
          }
        }
      } catch (err) {
        setError(err instanceof Error ? err.message : "Erro desconhecido")
      } finally {
        setLoading(false)
      }
    }
    fetchCourseDetails()
  }, [courseId])

  if (loading) {
    return (
      <div className="flex flex-col items-center justify-center h-64 gap-4">
        <Loader2 className="h-8 w-8 animate-spin text-primary" />
        <p className="text-muted-foreground">A carregar estrutura do curso...</p>
      </div>
    )
  }

  if (error || !course) {
    return (
      <Card className="border-destructive bg-destructive/5">
        <CardContent className="p-6 flex flex-col items-center gap-4">
          <p className="text-destructive font-medium">Erro: {error || "Curso não encontrado"}</p>
          <Button onClick={onBack}>Voltar</Button>
        </CardContent>
      </Card>
    )
  }

  // Cálculos de progresso baseados nos dados reais
  const allModules = course.sections.flatMap((s: any) => s.modules)
  const totalLessons = allModules.length
  const completedLessons = allModules.filter((m: any) => m.completion).length
  const progress = totalLessons > 0 ? Math.round((completedLessons / totalLessons) * 100) : 0

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-start gap-4">
        <Button variant="ghost" size="icon" onClick={onBack}>
          <ArrowLeft className="h-5 w-5" />
        </Button>
        <div className="flex-1">
          <div className="flex items-center gap-2">
            <h1 className="text-2xl font-bold text-foreground">{course.name}</h1>
            <Badge variant="outline">{course.shortname}</Badge>
          </div>
          <div 
            className="text-muted-foreground mt-1 text-sm line-clamp-2"
            dangerouslySetInnerHTML={{ __html: course.description }}
          />
          <div className="flex items-center gap-4 mt-4 text-sm text-muted-foreground">
            <span className="flex items-center gap-1">
              <Clock className="h-4 w-4" />
              Duração N/A
            </span>
            <span className="flex items-center gap-1">
              <Users className="h-4 w-4" />
              {course.enrolled} inscritos
            </span>
            <span className="flex items-center gap-1 text-xs bg-muted px-2 py-0.5 rounded">
              ID: {course.id}
            </span>
          </div>
        </div>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <div className="h-10 w-10 rounded-lg bg-primary/10 flex items-center justify-center">
                <Users className="h-5 w-5 text-primary" />
              </div>
              <div>
                <p className="text-2xl font-bold text-foreground">{course.enrolled}</p>
                <p className="text-sm text-muted-foreground">Inscritos</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <div className="h-10 w-10 rounded-lg bg-green-500/10 flex items-center justify-center">
                <CheckCircle2 className="h-5 w-5 text-green-500" />
              </div>
              <div>
                <p className="text-2xl font-bold text-foreground">{completedLessons}</p>
                <p className="text-sm text-muted-foreground">Atividades Concluídas</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <div className="h-10 w-10 rounded-lg bg-amber-500/10 flex items-center justify-center">
                <BarChart3 className="h-5 w-5 text-amber-500" />
              </div>
              <div>
                <p className="text-2xl font-bold text-foreground">{progress}%</p>
                <p className="text-sm text-muted-foreground">O teu Progresso</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <div className="h-10 w-10 rounded-lg bg-blue-500/10 flex items-center justify-center">
                <Award className="h-5 w-5 text-blue-500" />
              </div>
              <div>
                <p className="text-2xl font-bold text-foreground">{course.sections.length}</p>
                <p className="text-sm text-muted-foreground">Secções</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Tabs */}
      <Tabs value={activeTab} onValueChange={setActiveTab}>
        <TabsList>
          <TabsTrigger value="content">Conteúdo do Curso</TabsTrigger>
          <TabsTrigger value="feedback">Avaliações</TabsTrigger>
        </TabsList>

        <TabsContent value="content" className="mt-6">
          <Card>
            <CardHeader>
              <div className="flex items-center justify-between">
                <CardTitle>Estrutura e Módulos</CardTitle>
                <div className="flex items-center gap-4 text-sm">
                  <span className="text-muted-foreground font-medium">{completedLessons}/{totalLessons} completas</span>
                  <div className="w-32">
                    <Progress value={progress} className="h-2" />
                  </div>
                </div>
              </div>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                {course.sections.map((section: any, sIndex: number) => {
                  // Filtrar módulos vazios ou sem nome (como a secção Geral se estiver vazia)
                  if (section.modules.length === 0 && section.name === "General") return null;
                  
                  return (
                    <div key={section.id} className="border border-border rounded-xl overflow-hidden shadow-sm">
                      <button
                        onClick={() => setExpandedModule(expandedModule === section.id ? null : section.id)}
                        className="w-full flex items-center justify-between p-5 hover:bg-muted/50 transition-all bg-muted/20"
                      >
                        <div className="flex items-center gap-4">
                          <div className="h-9 w-9 rounded-lg bg-primary/10 flex items-center justify-center text-primary font-bold text-sm border border-primary/20">
                            {sIndex + 1}
                          </div>
                          <div className="text-left">
                            <p className="font-semibold text-foreground text-lg">{section.name || `Secção ${sIndex + 1}`}</p>
                            <p className="text-sm text-muted-foreground">{section.modules.length} atividades disponíveis</p>
                          </div>
                        </div>
                        <div className="flex items-center gap-3">
                           {expandedModule === section.id ? 
                             <div className="text-xs font-bold text-primary uppercase tracking-wider">Fechar</div> : 
                             <div className="text-xs font-bold text-muted-foreground uppercase tracking-wider">Expandir</div>
                           }
                        </div>
                      </button>
                      
                      {expandedModule === section.id && (
                        <div className="border-t border-border bg-white divide-y divide-border/50">
                          {section.modules.map((module: any) => (
                            <div 
                              key={module.id}
                              className={cn(
                                "group flex items-center gap-5 p-5 transition-colors cursor-pointer",
                                module.locked ? "opacity-60 cursor-not-allowed" : "hover:bg-primary/[0.02]"
                              )}
                              onClick={() => !module.locked && onStartActivity(module.id.toString())}
                            >
                              <div className={cn(
                                "h-10 w-10 rounded-xl flex items-center justify-center transition-transform group-hover:scale-110 shadow-sm",
                                module.completion ? "bg-green-500/10 border border-green-200" : "bg-muted border border-border"
                              )}>
                                {module.locked ? (
                                  <Lock className="h-5 w-5 text-muted-foreground" />
                                ) : module.completion ? (
                                  <CheckCircle2 className="h-5 w-5 text-green-600" />
                                ) : module.modname === "page" ? (
                                  <BookOpen className="h-5 w-5 text-blue-500" />
                                ) : module.modname === "quiz" ? (
                                  <FileQuestion className="h-5 w-5 text-amber-500" />
                                ) : (
                                  <FileText className="h-5 w-5 text-muted-foreground" />
                                )}
                              </div>
                              <div className="flex-1">
                                <p className={cn(
                                  "font-semibold text-base transition-colors",
                                  module.completion ? "text-muted-foreground" : "text-foreground group-hover:text-primary"
                                )}>
                                  {module.name}
                                </p>
                                <div className="flex items-center gap-3 mt-1">
                                  <Badge variant="secondary" className="text-[10px] uppercase font-bold px-1.5 h-4">
                                    {module.modname === "page" ? "Conteúdo" : module.modname === "quiz" ? "Exame" : module.modname}
                                  </Badge>
                                  {module.completion && (
                                    <span className="text-[11px] text-green-600 font-bold flex items-center gap-1">
                                      <CheckCircle2 className="h-3 w-3" /> CONCLUÍDO
                                    </span>
                                  )}
                                  {module.locked && (
                                    <span className="text-[11px] text-muted-foreground font-bold flex items-center gap-1">
                                      <Lock className="h-3 w-3" /> BLOQUEADO
                                    </span>
                                  )}
                                </div>
                              </div>
                              <Button 
                                variant={module.completion ? "outline" : "default"} 
                                size="sm" 
                                className="font-bold shadow-sm"
                                disabled={module.locked}
                                onClick={(e) => {
                                  e.stopPropagation();
                                  if (!module.locked) onStartActivity(module.id.toString());
                                }}
                              >
                                {module.locked ? "Bloqueado" : module.completion ? "Rever" : "Iniciar"}
                              </Button>
                            </div>
                          ))}
                        </div>
                      )}
                    </div>
                  )
                })}
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="feedback" className="mt-6">
          <Card>
            <CardContent className="p-12 text-center">
              <MessageSquare className="h-12 w-12 text-muted-foreground mx-auto mb-4 opacity-20" />
              <h3 className="text-lg font-medium">Ainda sem avaliações</h3>
              <p className="text-muted-foreground max-w-xs mx-auto mt-2">
                As avaliações dos colaboradores aparecerão aqui assim que terminarem o curso.
              </p>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  )
}
