"use client"

import { useState, useEffect } from "react"
import { ArrowLeft, Send, BookOpen, FileQuestion, CheckCircle2, AlertCircle, Loader2, ChevronDown, ChevronRight, ExternalLink } from "lucide-react"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { cn } from "@/lib/utils"
import type { MoodleCourse, Question, Activity } from "@/lib/types"

interface CoursePreviewProps {
  course: MoodleCourse
  onBack: () => void
  onSendToMoodle: () => void
  isSending: boolean
  moodleConnected: boolean
  createdCourseId?: number | string | null
  error?: string | null
}

function SafeRender({ value }: { value: any }) {
  if (value === null || value === undefined) return "-";
  if (typeof value === 'object') return JSON.stringify(value);
  return value.toString();
}

function QuestionPreview({ question, index }: { question: Question; index: number }) {
  const [expanded, setExpanded] = useState(false)

  return (
    <div className="border border-border rounded-lg p-4">
      <button
        onClick={() => setExpanded(!expanded)}
        className="w-full flex items-start justify-between text-left"
      >
        <div className="flex items-start gap-3">
          <span className="text-sm font-medium text-muted-foreground">{index + 1}.</span>
          <div>
            <p className="font-medium text-foreground">{question.name}</p>
            <Badge variant="outline" className="mt-1">
              {question.qtype === "multichoice" && "Escolha Multipla"}
              {question.qtype === "truefalse" && "Verdadeiro/Falso"}
              {question.qtype === "matching" && "Correspondencia"}
            </Badge>
          </div>
        </div>
        {expanded ? (
          <ChevronDown className="h-5 w-5 text-muted-foreground" />
        ) : (
          <ChevronRight className="h-5 w-5 text-muted-foreground" />
        )}
      </button>
      
      {expanded && (
        <div className="mt-4 pl-8 space-y-3">
          <p className="text-sm text-foreground">{question.questiontext}</p>
          
          {question.qtype === "multichoice" && question.answers && (
            <div className="space-y-2">
              {question.answers.map((answer, i) => (
                <div
                  key={i}
                  className={cn(
                    "text-sm p-2 rounded",
                    answer.fraction === 1 ? "bg-green-100 text-green-800" : "bg-muted"
                  )}
                >
                  {answer.text}
                  {answer.fraction === 1 && (
                    <CheckCircle2 className="inline-block ml-2 h-4 w-4" />
                  )}
                </div>
              ))}
            </div>
          )}
          
          {question.qtype === "truefalse" && (
            <div className="space-y-2">
              <div className={cn(
                "text-sm p-2 rounded",
                question.correctanswer ? "bg-green-100 text-green-800" : "bg-muted"
              )}>
                Verdadeiro {question.correctanswer && <CheckCircle2 className="inline-block ml-2 h-4 w-4" />}
              </div>
              <div className={cn(
                "text-sm p-2 rounded",
                !question.correctanswer ? "bg-green-100 text-green-800" : "bg-muted"
              )}>
                Falso {!question.correctanswer && <CheckCircle2 className="inline-block ml-2 h-4 w-4" />}
              </div>
            </div>
          )}
          
          {question.qtype === "matching" && question.subquestions && (
            <div className="space-y-2">
              {question.subquestions.map((sq, i) => (
                <div key={i} className="text-sm p-2 rounded bg-muted flex justify-between">
                  <span>{sq.text}</span>
                  <span className="text-primary">{sq.answer}</span>
                </div>
              ))}
            </div>
          )}
          
          {question.feedback && (
            <p className="text-sm text-muted-foreground italic">
              Feedback: {question.feedback}
            </p>
          )}
        </div>
      )}
    </div>
  )
}

function ActivityPreview({ activity, index, imageFolder }: { activity: Activity; index: number; imageFolder?: string }) {
  const [expanded, setExpanded] = useState(index === 0)

  // Função para transformar placeholders em imagens REAIS no preview
  const processPreviewContent = (html: string) => {
    if (!html) return "";
    let processed = html;

    // 1. Processar Imagens [[IMG_Pxx_yy]]
    // Tenta pegar do env ou assume localhost:8080
    const moodleUrl = process.env.NEXT_PUBLIC_MOODLE_URL || "http://localhost:8080";
    const imgRegex = /\[\[IMG_P?(\d+)_(\d+)(?:_([^\]]+))?\]\]/gi;
    
    processed = processed.replace(imgRegex, (match, p, s, suffix) => {
      if (!imageFolder) return `<div class="p-4 border-2 border-dashed border-red-200 bg-red-50 text-red-500 rounded-lg text-center my-4 font-bold">⚠️ Extração Necessária: ${match}</div>`;
      
      const pPad = p.padStart(3, '0');
      const sPad = s.padStart(3, '0');
      
      // Usar o proxy get_image.php para evitar erros de CORS e Caminho
      const imgPath = `${imageFolder}/img-${pPad}-${sPad}.jpg`;
      const imgUrl = `${moodleUrl}/local/wsmanageactivities/get_image.php?path=${imgPath}`;
      
      return `
        <figure class="my-6 text-center">
          <img src="${imgUrl}" 
               crossorigin="anonymous"
               class="rounded-lg shadow-md mx-auto max-w-full h-auto border border-gray-100" 
               alt="${match}"
               onerror="this.src='${moodleUrl}/local/wsmanageactivities/get_image.php?path=${imageFolder}/img-${pPad}-000.jpg'; this.onerror=() => { this.parentElement.innerHTML='<div class=\\'p-4 border-2 border-dashed border-amber-200 bg-amber-50 text-amber-500 rounded-lg text-center my-4\\'>🖼️ Imagem não encontrada (Pág ${p}): ${match}</div>' }" />
          <figcaption class="mt-3 text-[10px] uppercase tracking-widest text-gray-400 font-bold">Preview Extração: ${imageFolder}</figcaption>
        </figure>
      `;
    });

    // 2. Processar Tabelas [[TABLE_Pxx]]
    const tableRegex = /\[\[TABLE_P?(\d+)(?:_([^\]]+))?\]\]/gi;
    processed = processed.replace(tableRegex, (match, p) => {
      return `<div class="p-6 border-2 border-dashed border-blue-200 bg-blue-50 text-blue-600 rounded-xl text-center my-6">
        <div class="font-bold text-lg mb-1">📊 TABELA: Página ${p}</div>
        <div class="text-xs opacity-75">${match}</div>
      </div>`;
    });

    return processed;
  };

  return (
    <div className="border border-border rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow">
      <button
        onClick={() => setExpanded(!expanded)}
        className="w-full flex items-center justify-between p-4 bg-muted/30 hover:bg-muted/70 transition-colors"
      >
        <div className="flex items-center gap-3">
          <span className="text-sm font-bold text-muted-foreground/60 w-6">{index + 1}.</span>
          <div className="flex items-center gap-2">
            {activity.type === "page" && <BookOpen className="h-4 w-4 text-primary" />}
            {activity.type === "quiz" && <FileQuestion className="h-4 w-4 text-primary" />}
            <span className="font-semibold text-foreground">{activity.name}</span>
          </div>
          <Badge variant="secondary" className="text-[10px] uppercase font-bold tracking-wider">
            {activity.type === "page" && "Pagina"}
            {activity.type === "quiz" && "Quiz"}
          </Badge>
        </div>
        {expanded ? (
          <ChevronDown className="h-5 w-5 text-muted-foreground" />
        ) : (
          <ChevronRight className="h-5 w-5 text-muted-foreground" />
        )}
      </button>
      
      {expanded && (
        <div className="p-6 border-t border-border bg-white">
          {activity.type === "page" && activity.content && (
            <div 
              className="prose prose-sm max-w-none prose-headings:text-primary prose-strong:text-foreground"
              dangerouslySetInnerHTML={{ __html: processPreviewContent(activity.content) }}
            />
          )}
          {activity.type === "quiz" && (
            <div className="space-y-3">
              {activity.intro && <p className="text-sm text-muted-foreground border-l-4 border-primary/20 pl-4 py-1 italic">{activity.intro}</p>}
              <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div className="p-3 bg-muted/50 rounded-xl border border-muted">
                  <p className="text-[10px] uppercase font-bold text-muted-foreground mb-1">Duracao</p>
                  <p className="font-bold">{activity.timelimit ? `${activity.timelimit / 60} min` : "Sem limite"}</p>
                </div>
                <div className="p-3 bg-muted/50 rounded-xl border border-muted">
                  <p className="text-[10px] uppercase font-bold text-muted-foreground mb-1">Tentativas</p>
                  <p className="font-bold">{activity.attempts || "Ilimitadas"}</p>
                </div>
                <div className="p-3 bg-muted/50 rounded-xl border border-muted">
                  <p className="text-[10px] uppercase font-bold text-muted-foreground mb-1">Nota Minima</p>
                  <p className="font-bold">
                    {activity.gradepass ? `${activity.gradepass}/20 (${(activity.gradepass/20*100).toFixed(0)}%)` : "0%"}
                  </p>
                </div>
                <div className="p-3 bg-muted/50 rounded-xl border border-muted">
                  <p className="text-[10px] uppercase font-bold text-muted-foreground mb-1">Banco</p>
                  <p className="font-bold truncate">
                    {typeof activity.questions_from_bank === 'object' 
                      ? activity.questions_from_bank.bank_name 
                      : "Geral"}
                  </p>
                </div>
              </div>
            </div>
          )}
        </div>
      )}
    </div>
  )
}

export function CoursePreview({ 
  course, 
  onBack, 
  onSendToMoodle, 
  isSending, 
  moodleConnected, 
  createdCourseId,
  error 
}: CoursePreviewProps) {
  const [success, setSuccess] = useState(false)
  const [courseId, setCourseId] = useState<string | number | null>(null)

  // Debug para detetar se a pasta está a chegar
  useEffect(() => {
    console.log("CoursePreview: Pasta de imagens recebida ->", course.image_folder);
  }, [course.image_folder]);

  useEffect(() => {
    if (createdCourseId) {
      setSuccess(true)
      setCourseId(createdCourseId)
    }
  }, [createdCourseId])

  const totalQuestions = (course.question_banks || []).reduce(
    (acc, bank) => acc + (bank.questions || []).length, 
    0
  )

  const isSuccess = success || (createdCourseId && !!createdCourseId);

  return (
    <div className="space-y-6 pb-20">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-4">
          <Button variant="ghost" size="icon" onClick={onBack}>
            <ArrowLeft className="h-5 w-5" />
          </Button>
          <div>
            <h1 className="text-2xl font-bold text-foreground">Preview do Curso</h1>
            <p className="text-muted-foreground">Revise o conteudo antes de enviar para o Moodle</p>
          </div>
        </div>
      </div>

      {/* Error Message */}
      {error && (
        <Alert variant="destructive">
          <AlertCircle className="h-4 w-4" />
          <AlertTitle>Erro ao enviar para o Moodle</AlertTitle>
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}

      {/* Connection Warning */}
      {!moodleConnected && (
        <Card className="border-amber-500 bg-amber-50">
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <AlertCircle className="h-5 w-5 text-amber-600" />
              <div>
                <p className="font-medium text-amber-800">Moodle nao conectado</p>
                <p className="text-sm text-amber-700">Configure as variaveis de ambiente para enviar o curso.</p>
              </div>
            </div>
          </CardContent>
        </Card>
      )}

      <Tabs defaultValue="activities" className="w-full">
        <TabsList className="grid w-full grid-cols-2">
          <TabsTrigger value="activities" className="gap-2">
            <BookOpen className="h-4 w-4" />
            Estrutura de Paginas ({(course.activities || []).length})
          </TabsTrigger>
          <TabsTrigger value="questions" className="gap-2">
            <FileQuestion className="h-4 w-4" />
            Banco de Questoes ({totalQuestions})
          </TabsTrigger>
        </TabsList>
        
        <TabsContent value="activities" className="space-y-4 mt-4">
          {(course.activities || []).map((activity, index) => (
            <ActivityPreview key={index} activity={activity} index={index} imageFolder={course.image_folder} />
          ))}
          {(course.activities || []).length === 0 && (
            <div className="text-center py-10 text-muted-foreground italic">Nenhuma atividade gerada.</div>
          )}
        </TabsContent>
        
        <TabsContent value="questions" className="space-y-4 mt-4">
          {(course.question_banks || []).map((bank) => (
            <Card key={bank.name}>
              <CardHeader>
                <CardTitle className="text-lg">{bank.name}</CardTitle>
              </CardHeader>
              <CardContent className="space-y-3">
                {(bank.questions || []).map((question, index) => (
                  <QuestionPreview key={index} question={question} index={index} />
                ))}
              </CardContent>
            </Card>
          ))}
          {(course.question_banks || []).length === 0 && (
            <div className="text-center py-10 text-muted-foreground italic">Nenhum banco de questões gerado.</div>
          )}
        </TabsContent>
      </Tabs>

      {/* Bottom Actions and Success Message */}
      <div className="pt-6 border-t border-border space-y-4">
        {isSuccess && (
          <Card className="border-green-500 bg-green-50 shadow-sm animate-in fade-in slide-in-from-bottom-2 duration-300">
            <CardContent className="p-4">
              <div className="flex items-center gap-4">
                <div className="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center text-green-600">
                  <CheckCircle2 className="h-6 w-6" />
                </div>
                <div className="flex-1">
                  <h3 className="text-sm font-bold text-green-800">Curso Criado com Sucesso!</h3>
                  <p className="text-green-700 text-xs">
                    Importado com {course.activities.length} atividades e {totalQuestions} questões. 
                    <span className="font-bold ml-1">(ID: {courseId || createdCourseId})</span>
                  </p>
                </div>
                <Button asChild size="sm" className="bg-green-600 hover:bg-green-700 text-white gap-2">
                  <a 
                    href={`${process.env.NEXT_PUBLIC_MOODLE_URL || "http://localhost:8080"}/course/view.php?id=${courseId || createdCourseId}`} 
                    target="_blank" 
                    rel="noopener noreferrer"
                  >
                    <ExternalLink className="h-3.5 w-3.5" />
                    Abrir no Moodle
                  </a>
                </Button>
              </div>
            </CardContent>
          </Card>
        )}

        <div className="flex justify-between items-center">
          <Button variant="outline" onClick={onBack}>
            <ArrowLeft className="h-4 w-4 mr-2" />
            Voltar e Editar
          </Button>
          <Button 
            size="lg" 
            onClick={onSendToMoodle}
            disabled={isSending || !moodleConnected}
            className={cn("gap-2", isSuccess && "bg-green-600 hover:bg-green-700")}
          >
            {isSending ? (
              <>
                <Loader2 className="h-5 w-5 animate-spin" />
                A Enviar...
              </>
            ) : isSuccess ? (
              <>
                <Send className="h-5 w-5" />
                Re-enviar para Moodle
              </>
            ) : (
              <>
                <Send className="h-5 w-5" />
                Enviar para Moodle
              </>
            )}
          </Button>
        </div>
      </div>
    </div>
  )
}
