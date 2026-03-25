"use client"

import { useState } from "react"
import { ArrowLeft, Send, BookOpen, FileQuestion, CheckCircle2, AlertCircle, Loader2, ChevronDown, ChevronRight } from "lucide-react"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { cn } from "@/lib/utils"
import type { MoodleCourse, Question, Activity } from "@/lib/types"

interface CoursePreviewProps {
  course: MoodleCourse
  onBack: () => void
  onSendToMoodle: () => void
  isSending: boolean
  moodleConnected: boolean
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

function ActivityPreview({ activity, index }: { activity: Activity; index: number }) {
  const [expanded, setExpanded] = useState(index === 0)

  return (
    <div className="border border-border rounded-lg overflow-hidden">
      <button
        onClick={() => setExpanded(!expanded)}
        className="w-full flex items-center justify-between p-4 bg-muted/50 hover:bg-muted transition-colors"
      >
        <div className="flex items-center gap-3">
          <span className="text-sm font-medium text-muted-foreground">{index + 1}.</span>
          <div className="flex items-center gap-2">
            {activity.type === "page" && <BookOpen className="h-4 w-4 text-primary" />}
            {activity.type === "quiz" && <FileQuestion className="h-4 w-4 text-primary" />}
            <span className="font-medium text-foreground">{activity.name}</span>
          </div>
          <Badge variant="outline">
            {activity.type === "page" && "Pagina"}
            {activity.type === "quiz" && "Quiz"}
            {activity.type === "lesson" && "Licao"}
          </Badge>
        </div>
        {expanded ? (
          <ChevronDown className="h-5 w-5 text-muted-foreground" />
        ) : (
          <ChevronRight className="h-5 w-5 text-muted-foreground" />
        )}
      </button>
      
      {expanded && (
        <div className="p-4 border-t border-border">
          {activity.type === "page" && activity.content && (
            <div 
              className="prose prose-sm max-w-none"
              dangerouslySetInnerHTML={{ __html: activity.content }}
            />
          )}
          {activity.type === "quiz" && (
            <div className="space-y-3">
              {activity.intro && <p className="text-sm text-muted-foreground">{activity.intro}</p>}
              <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div className="p-3 bg-muted rounded-lg">
                  <p className="text-muted-foreground">Duracao</p>
                  <p className="font-medium">{activity.timelimit ? `${activity.timelimit / 60} min` : "Sem limite"}</p>
                </div>
                <div className="p-3 bg-muted rounded-lg">
                  <p className="text-muted-foreground">Tentativas</p>
                  <p className="font-medium">{activity.attempts || "Ilimitadas"}</p>
                </div>
                <div className="p-3 bg-muted rounded-lg">
                  <p className="text-muted-foreground">Nota Minima</p>
                  <p className="font-medium">{activity.gradepass || 0}%</p>
                </div>
                <div className="p-3 bg-muted rounded-lg">
                  <p className="text-muted-foreground">Banco de Questoes</p>
                  <p className="font-medium truncate">
                    {typeof activity.questions_from_bank === 'object' 
                      ? `${activity.questions_from_bank.bank_name} (${activity.questions_from_bank.count} qts)`
                      : <SafeRender value={activity.questions_from_bank} />}
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

export function CoursePreview({ course, onBack, onSendToMoodle, isSending, moodleConnected }: CoursePreviewProps) {
  const totalQuestions = course.question_banks.reduce(
    (acc, bank) => acc + bank.questions.length, 
    0
  )

  return (
    <div className="space-y-6">
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
        <Button 
          size="lg" 
          onClick={onSendToMoodle}
          disabled={isSending || !moodleConnected}
          className="gap-2"
        >
          {isSending ? (
            <>
              <Loader2 className="h-5 w-5 animate-spin" />
              A Enviar...
            </>
          ) : (
            <>
              <Send className="h-5 w-5" />
              Enviar para Moodle
            </>
          )}
        </Button>
      </div>

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

      {/* Course Overview */}
      <Card>
        <CardHeader>
          <CardTitle>Informacoes do Curso</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <p className="text-sm text-muted-foreground">Nome do Curso</p>
              <p className="font-medium text-foreground">{course.course_name}</p>
            </div>
            <div>
              <p className="text-sm text-muted-foreground">Sigla</p>
              <p className="font-medium text-foreground">{course.course_shortname}</p>
            </div>
            <div>
              <p className="text-sm text-muted-foreground">Ficheiro Fonte</p>
              <p className="font-medium text-foreground">{course.source_file}</p>
            </div>
            <div>
              <p className="text-sm text-muted-foreground">Total de Questoes</p>
              <p className="font-medium text-foreground">{totalQuestions}</p>
            </div>
          </div>
          <div>
            <p className="text-sm text-muted-foreground">Resumo</p>
            <p className="text-foreground">{course.course_summary}</p>
          </div>
        </CardContent>
      </Card>

      {/* Tabs for Activities and Questions */}
      <Tabs defaultValue="activities" className="w-full">
        <TabsList className="grid w-full grid-cols-2">
          <TabsTrigger value="activities" className="gap-2">
            <BookOpen className="h-4 w-4" />
            Atividades ({course.activities.length})
          </TabsTrigger>
          <TabsTrigger value="questions" className="gap-2">
            <FileQuestion className="h-4 w-4" />
            Banco de Questoes ({totalQuestions})
          </TabsTrigger>
        </TabsList>
        
        <TabsContent value="activities" className="space-y-4 mt-4">
          {course.activities.map((activity, index) => (
            <ActivityPreview key={index} activity={activity} index={index} />
          ))}
        </TabsContent>
        
        <TabsContent value="questions" className="space-y-4 mt-4">
          {course.question_banks.map((bank) => (
            <Card key={bank.name}>
              <CardHeader>
                <CardTitle className="text-lg">{bank.name}</CardTitle>
              </CardHeader>
              <CardContent className="space-y-3">
                {bank.questions.map((question, index) => (
                  <QuestionPreview key={index} question={question} index={index} />
                ))}
              </CardContent>
            </Card>
          ))}
        </TabsContent>
      </Tabs>

      {/* Bottom Actions */}
      <div className="flex justify-between pt-4 border-t border-border">
        <Button variant="outline" onClick={onBack}>
          <ArrowLeft className="h-4 w-4 mr-2" />
          Voltar e Editar
        </Button>
        <Button 
          size="lg" 
          onClick={onSendToMoodle}
          disabled={isSending || !moodleConnected}
          className="gap-2"
        >
          {isSending ? (
            <>
              <Loader2 className="h-5 w-5 animate-spin" />
              A Enviar...
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
  )
}
