"use client"

import { useState } from "react"
import { 
  ArrowLeft, 
  Play, 
  Users, 
  Clock, 
  FileText, 
  CheckCircle2, 
  Lock,
  Award,
  BarChart3,
  Video,
  FileQuestion,
  BookOpen,
  Star,
  ThumbsUp,
  ThumbsDown,
  MessageSquare
} from "lucide-react"
import { StarRating } from "./star-rating"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Progress } from "@/components/ui/progress"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { cn } from "@/lib/utils"

interface CourseDetailProps {
  courseId: string | null
  onBack: () => void
}

const courseData = {
  id: "1",
  name: "Segurança no Trabalho - Normas ISO 45001",
  description: "Formação completa sobre normas de segurança ocupacional e gestão de riscos no ambiente de trabalho. Este curso foi gerado automaticamente a partir do manual técnico ISO 45001.",
  sourceFile: "manual_iso_45001.pdf",
  createdAt: "2024-01-15",
  duration: "4h 30min",
  enrolled: 45,
  completions: 38,
  avgScore: 87,
  rating: 4.7,
  totalReviews: 38,
  qualityScore: 92,
  courseEvaluation: {
    overall: 4.7,
    contentQuality: 4.8,
    videoClarity: 4.5,
    examRelevance: 4.6,
    practicalApplicability: 4.9,
    recommendRate: 94,
    reviews: [
      { 
        user: "João Silva", 
        rating: 5, 
        comment: "Excelente curso! O conteúdo gerado está muito bem estruturado e os vídeos são claros.", 
        date: "2024-02-10",
        helpful: 12
      },
      { 
        user: "Maria Santos", 
        rating: 4, 
        comment: "Bom curso, mas alguns quizzes poderiam ter mais questões práticas.", 
        date: "2024-02-08",
        helpful: 8
      },
      { 
        user: "Pedro Costa", 
        rating: 5, 
        comment: "Muito útil para a certificação. Os exames refletem bem o conteúdo do manual.", 
        date: "2024-02-05",
        helpful: 15
      },
      { 
        user: "Ana Oliveira", 
        rating: 4, 
        comment: "Conteúdo completo. Sugiro adicionar mais exemplos práticos nos vídeos.", 
        date: "2024-01-28",
        helpful: 6
      },
    ]
  },
  modules: [
    {
      id: "m1",
      title: "Introdução à ISO 45001",
      duration: "30min",
      lessons: [
        { id: "l1", title: "O que é a ISO 45001?", type: "video", duration: "8min", completed: true },
        { id: "l2", title: "Histórico e evolução das normas", type: "video", duration: "12min", completed: true },
        { id: "l3", title: "Benefícios da implementação", type: "video", duration: "10min", completed: true },
      ]
    },
    {
      id: "m2",
      title: "Contexto da Organização",
      duration: "45min",
      lessons: [
        { id: "l4", title: "Compreender a organização", type: "video", duration: "15min", completed: true },
        { id: "l5", title: "Partes interessadas", type: "video", duration: "12min", completed: true },
        { id: "l6", title: "Quiz: Contexto Organizacional", type: "quiz", duration: "10min", completed: false },
      ]
    },
    {
      id: "m3",
      title: "Liderança e Participação",
      duration: "50min",
      lessons: [
        { id: "l7", title: "Compromisso da gestão", type: "video", duration: "15min", completed: false },
        { id: "l8", title: "Política de SST", type: "video", duration: "12min", completed: false },
        { id: "l9", title: "Funções e responsabilidades", type: "video", duration: "13min", completed: false },
        { id: "l10", title: "Quiz: Liderança", type: "quiz", duration: "10min", completed: false },
      ]
    },
    {
      id: "m4",
      title: "Planeamento",
      duration: "55min",
      lessons: [
        { id: "l11", title: "Identificação de perigos", type: "video", duration: "18min", completed: false },
        { id: "l12", title: "Avaliação de riscos", type: "video", duration: "15min", completed: false },
        { id: "l13", title: "Objetivos e planos de ação", type: "video", duration: "12min", completed: false },
        { id: "l14", title: "Quiz: Planeamento", type: "quiz", duration: "10min", completed: false },
      ]
    },
    {
      id: "m5",
      title: "Exame Final",
      duration: "30min",
      lessons: [
        { id: "l15", title: "Exame de Certificação", type: "exam", duration: "30min", completed: false },
      ]
    },
  ],
  enrolledUsers: [
    { name: "João Silva", progress: 100, score: 92, certified: true },
    { name: "Maria Santos", progress: 85, score: 88, certified: false },
    { name: "Pedro Costa", progress: 60, score: null, certified: false },
    { name: "Ana Oliveira", progress: 100, score: 95, certified: true },
    { name: "Rui Ferreira", progress: 45, score: null, certified: false },
  ]
}

export function CourseDetail({ courseId, onBack }: CourseDetailProps) {
  const [activeTab, setActiveTab] = useState("content")
  const [expandedModule, setExpandedModule] = useState<string | null>("m1")

  const totalLessons = courseData.modules.reduce((acc, m) => acc + m.lessons.length, 0)
  const completedLessons = courseData.modules.reduce(
    (acc, m) => acc + m.lessons.filter(l => l.completed).length, 
    0
  )
  const progress = Math.round((completedLessons / totalLessons) * 100)

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-start gap-4">
        <Button variant="ghost" size="icon" onClick={onBack}>
          <ArrowLeft className="h-5 w-5" />
        </Button>
        <div className="flex-1">
          <h1 className="text-2xl font-bold text-foreground">{courseData.name}</h1>
          <p className="text-muted-foreground mt-1">{courseData.description}</p>
          <div className="flex items-center gap-4 mt-4 text-sm text-muted-foreground">
            <span className="flex items-center gap-1">
              <Clock className="h-4 w-4" />
              {courseData.duration}
            </span>
            <span className="flex items-center gap-1">
              <Users className="h-4 w-4" />
              {courseData.enrolled} inscritos
            </span>
            <span className="flex items-center gap-1">
              <FileText className="h-4 w-4" />
              {courseData.sourceFile}
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
                <p className="text-2xl font-bold text-foreground">{courseData.enrolled}</p>
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
                <p className="text-2xl font-bold text-foreground">{courseData.completions}</p>
                <p className="text-sm text-muted-foreground">Conclusões</p>
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
                <p className="text-2xl font-bold text-foreground">{courseData.avgScore}%</p>
                <p className="text-sm text-muted-foreground">Nota Média</p>
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
                <p className="text-2xl font-bold text-foreground">{courseData.enrolledUsers.filter(u => u.certified).length}</p>
                <p className="text-sm text-muted-foreground">Certificados</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Tabs */}
      <Tabs value={activeTab} onValueChange={setActiveTab}>
        <TabsList>
          <TabsTrigger value="content">Conteúdo</TabsTrigger>
          <TabsTrigger value="students">Colaboradores</TabsTrigger>
          <TabsTrigger value="feedback">Avaliações</TabsTrigger>
          <TabsTrigger value="analytics">Análises</TabsTrigger>
        </TabsList>

        <TabsContent value="content" className="mt-6">
          <Card>
            <CardHeader>
              <div className="flex items-center justify-between">
                <CardTitle>Módulos do Curso</CardTitle>
                <div className="flex items-center gap-2 text-sm">
                  <span className="text-muted-foreground">{completedLessons}/{totalLessons} lições</span>
                  <Progress value={progress} className="w-24 h-2" />
                </div>
              </div>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                {courseData.modules.map((module, moduleIndex) => (
                  <div key={module.id} className="border border-border rounded-lg overflow-hidden">
                    <button
                      onClick={() => setExpandedModule(expandedModule === module.id ? null : module.id)}
                      className="w-full flex items-center justify-between p-4 hover:bg-muted/50 transition-colors"
                    >
                      <div className="flex items-center gap-3">
                        <div className="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center text-primary font-medium text-sm">
                          {moduleIndex + 1}
                        </div>
                        <div className="text-left">
                          <p className="font-medium text-foreground">{module.title}</p>
                          <p className="text-sm text-muted-foreground">{module.lessons.length} lições - {module.duration}</p>
                        </div>
                      </div>
                      <div className="flex items-center gap-2">
                        <span className="text-sm text-muted-foreground">
                          {module.lessons.filter(l => l.completed).length}/{module.lessons.length}
                        </span>
                      </div>
                    </button>
                    
                    {expandedModule === module.id && (
                      <div className="border-t border-border bg-muted/30">
                        {module.lessons.map((lesson) => (
                          <div 
                            key={lesson.id}
                            className="flex items-center gap-4 p-4 hover:bg-muted/50 transition-colors cursor-pointer"
                          >
                            <div className={cn(
                              "h-8 w-8 rounded-full flex items-center justify-center",
                              lesson.completed ? "bg-green-500/10" : "bg-muted"
                            )}>
                              {lesson.completed ? (
                                <CheckCircle2 className="h-4 w-4 text-green-500" />
                              ) : lesson.type === "video" ? (
                                <Video className="h-4 w-4 text-muted-foreground" />
                              ) : lesson.type === "quiz" ? (
                                <FileQuestion className="h-4 w-4 text-muted-foreground" />
                              ) : (
                                <Award className="h-4 w-4 text-muted-foreground" />
                              )}
                            </div>
                            <div className="flex-1">
                              <p className={cn(
                                "font-medium",
                                lesson.completed ? "text-muted-foreground" : "text-foreground"
                              )}>
                                {lesson.title}
                              </p>
                              <p className="text-sm text-muted-foreground capitalize">
                                {lesson.type === "video" ? "Vídeo" : lesson.type === "quiz" ? "Quiz" : "Exame"} - {lesson.duration}
                              </p>
                            </div>
                            <Button variant="ghost" size="sm" disabled={!lesson.completed && moduleIndex > 0}>
                              {lesson.completed ? "Rever" : "Iniciar"}
                            </Button>
                          </div>
                        ))}
                      </div>
                    )}
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="students" className="mt-6">
          <Card>
            <CardHeader>
              <CardTitle>Colaboradores Inscritos</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                {courseData.enrolledUsers.map((user, index) => (
                  <div key={index} className="flex items-center gap-4 p-4 rounded-lg border border-border">
                    <div className="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center text-primary font-medium">
                      {user.name.split(" ").map(n => n[0]).join("")}
                    </div>
                    <div className="flex-1">
                      <p className="font-medium text-foreground">{user.name}</p>
                      <div className="flex items-center gap-2 mt-1">
                        <Progress value={user.progress} className="flex-1 h-2 max-w-[200px]" />
                        <span className="text-sm text-muted-foreground">{user.progress}%</span>
                      </div>
                    </div>
                    <div className="text-right">
                      {user.score !== null && (
                        <p className="text-sm font-medium text-foreground">Nota: {user.score}%</p>
                      )}
                      {user.certified && (
                        <span className="inline-flex items-center gap-1 text-xs text-green-600 bg-green-50 px-2 py-1 rounded-full">
                          <Award className="h-3 w-3" />
                          Certificado
                        </span>
                      )}
                    </div>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="feedback" className="mt-6">
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {/* Quality Overview */}
            <Card className="lg:col-span-1">
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <Star className="h-5 w-5 text-primary" />
                  Qualidade do Curso
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-6">
                <div className="text-center py-4">
                  <div className="text-5xl font-bold text-primary">{courseData.qualityScore}%</div>
                  <p className="text-muted-foreground mt-2">Pontuação de Qualidade</p>
                  <div className="mt-4">
                    <StarRating rating={courseData.rating} totalReviews={courseData.totalReviews} size="lg" />
                  </div>
                </div>
                
                <div className="space-y-4 pt-4 border-t border-border">
                  <div className="flex items-center justify-between">
                    <span className="text-sm text-muted-foreground">Qualidade do Conteúdo</span>
                    <div className="flex items-center gap-2">
                      <div className="w-24 h-2 bg-muted rounded-full overflow-hidden">
                        <div className="h-full bg-primary rounded-full" style={{ width: `${(courseData.courseEvaluation.contentQuality / 5) * 100}%` }} />
                      </div>
                      <span className="text-sm font-medium">{courseData.courseEvaluation.contentQuality}</span>
                    </div>
                  </div>
                  <div className="flex items-center justify-between">
                    <span className="text-sm text-muted-foreground">Clareza dos Vídeos</span>
                    <div className="flex items-center gap-2">
                      <div className="w-24 h-2 bg-muted rounded-full overflow-hidden">
                        <div className="h-full bg-primary rounded-full" style={{ width: `${(courseData.courseEvaluation.videoClarity / 5) * 100}%` }} />
                      </div>
                      <span className="text-sm font-medium">{courseData.courseEvaluation.videoClarity}</span>
                    </div>
                  </div>
                  <div className="flex items-center justify-between">
                    <span className="text-sm text-muted-foreground">Relevância dos Exames</span>
                    <div className="flex items-center gap-2">
                      <div className="w-24 h-2 bg-muted rounded-full overflow-hidden">
                        <div className="h-full bg-primary rounded-full" style={{ width: `${(courseData.courseEvaluation.examRelevance / 5) * 100}%` }} />
                      </div>
                      <span className="text-sm font-medium">{courseData.courseEvaluation.examRelevance}</span>
                    </div>
                  </div>
                  <div className="flex items-center justify-between">
                    <span className="text-sm text-muted-foreground">Aplicabilidade Prática</span>
                    <div className="flex items-center gap-2">
                      <div className="w-24 h-2 bg-muted rounded-full overflow-hidden">
                        <div className="h-full bg-primary rounded-full" style={{ width: `${(courseData.courseEvaluation.practicalApplicability / 5) * 100}%` }} />
                      </div>
                      <span className="text-sm font-medium">{courseData.courseEvaluation.practicalApplicability}</span>
                    </div>
                  </div>
                </div>

                <div className="pt-4 border-t border-border">
                  <div className="flex items-center justify-between">
                    <span className="text-sm text-muted-foreground">Taxa de Recomendação</span>
                    <span className="text-lg font-bold text-green-600">{courseData.courseEvaluation.recommendRate}%</span>
                  </div>
                  <p className="text-xs text-muted-foreground mt-1">
                    dos colaboradores recomendariam este curso
                  </p>
                </div>
              </CardContent>
            </Card>

            {/* Reviews */}
            <Card className="lg:col-span-2">
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <MessageSquare className="h-5 w-5 text-primary" />
                  Avaliações dos Colaboradores
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="space-y-4">
                  {courseData.courseEvaluation.reviews.map((review, index) => (
                    <div key={index} className="p-4 rounded-lg border border-border">
                      <div className="flex items-start justify-between mb-2">
                        <div className="flex items-center gap-3">
                          <div className="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center text-primary font-medium">
                            {review.user.split(" ").map(n => n[0]).join("")}
                          </div>
                          <div>
                            <p className="font-medium text-foreground">{review.user}</p>
                            <StarRating rating={review.rating} size="sm" showValue={false} />
                          </div>
                        </div>
                        <span className="text-xs text-muted-foreground">{review.date}</span>
                      </div>
                      <p className="text-sm text-muted-foreground mt-3">{review.comment}</p>
                      <div className="flex items-center gap-4 mt-3 pt-3 border-t border-border">
                        <button className="flex items-center gap-1 text-xs text-muted-foreground hover:text-primary transition-colors">
                          <ThumbsUp className="h-3.5 w-3.5" />
                          Útil ({review.helpful})
                        </button>
                      </div>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        <TabsContent value="analytics" className="mt-6">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <Card>
              <CardHeader>
                <CardTitle>Taxa de Conclusão</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="flex items-center justify-center h-48">
                  <div className="text-center">
                    <p className="text-5xl font-bold text-primary">84%</p>
                    <p className="text-muted-foreground mt-2">dos colaboradores completaram o curso</p>
                  </div>
                </div>
              </CardContent>
            </Card>
            <Card>
              <CardHeader>
                <CardTitle>Desempenho nos Exames</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="flex items-center justify-center h-48">
                  <div className="text-center">
                    <p className="text-5xl font-bold text-green-500">87%</p>
                    <p className="text-muted-foreground mt-2">nota média no exame final</p>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </TabsContent>
      </Tabs>
    </div>
  )
}
