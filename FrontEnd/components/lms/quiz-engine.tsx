"use client"

import { useState } from "react"
import { 
  CheckCircle2, 
  XCircle, 
  ArrowRight, 
  ArrowLeft,
  RotateCcw, 
  Trophy,
  AlertCircle
} from "lucide-react"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from "@/components/ui/card"
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group"
import { Label } from "@/components/ui/label"
import { Progress } from "@/components/ui/progress"
import { cn } from "@/lib/utils"

import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"

interface QuizEngineProps {
  quizData: any
  onComplete: (passed: boolean, score: number) => void
  onReview: () => void
  onFinish: () => void
}

export function QuizEngine({ quizData, onComplete, onReview, onFinish }: QuizEngineProps) {
  // Quiz State Machine
  // Controls the flow of the evaluation: intro -> answering -> result calculation -> review mode.
  const [currentStep, setCurrentStep] = useState<"intro" | "questions" | "result" | "review">("intro")
  const [currentQuestionIndex, setCurrentStepIndex] = useState(0)
  
  // Stores user responses mapped by question ID.
  // For matching questions, it stores a nested object of sub-question IDs to answers.
  const [answers, setAnswers] = useState<Record<number, any>>({})
  const [score, setScore] = useState(0)

  const questions = quizData.questions || []
  const currentQuestion = questions[currentQuestionIndex]
  const isLastQuestion = currentQuestionIndex === questions.length - 1

  const handleStart = () => setCurrentStep("questions")

  const handleNext = () => {
    if (currentStep === "review") {
      if (isLastQuestion) {
        setCurrentStep("result")
      } else {
        setCurrentStepIndex(prev => prev + 1)
      }
      return
    }

    if (isLastQuestion) {
      calculateResult()
    } else {
      setCurrentStepIndex(prev => prev + 1)
    }
  }

  const handleBack = () => {
    if (currentQuestionIndex > 0) {
      setCurrentStepIndex(prev => prev - 1)
    } else if (currentStep === "review") {
      setCurrentStep("result")
    }
  }

  // Scoring Engine
  // Iterates through all answered questions, comparing user inputs with the correct options
  // defined in the Moodle JSON payload. Normalizes the final score to a 0-20 scale.
  const calculateResult = () => {
    let totalScore = 0
    questions.forEach((q: any) => {
      if (q.type === 'match') {
        const subAnswers = answers[q.id] || {}
        let correctMatches = 0
        q.subquestions.forEach((sq: any) => {
          if (subAnswers[sq.id] === sq.correct_answer) {
            correctMatches++
          }
        })
        if (q.subquestions.length > 0) {
          totalScore += (correctMatches / q.subquestions.length)
        }
      } else {
        const selectedOptionId = answers[q.id]
        const correctOption = q.options.find((o: any) => o.is_correct)
        if (selectedOptionId === correctOption?.id) {
          totalScore += 1
        }
      }
    })

    // Normalizing the score to a standard Portuguese 20-point scale.
    const finalScore = (totalScore / questions.length) * 20 
    setScore(finalScore)
    setCurrentStep("result")
    
    // Passing criteria hardcoded to 75% (15/20) based on Moodle constraints.
    const passed = finalScore >= 15
    onComplete(passed, finalScore)
  }

  const handleRestart = () => {
    setCurrentStepIndex(0)
    setAnswers({})
    setCurrentStep("intro")
  }

  const handleStartReview = () => {
    setCurrentStepIndex(0)
    setCurrentStep("review")
  }

  // Validation
  // Ensures the user has selected an option (or all matching sub-options) before proceeding.
  const isQuestionAnswered = () => {
    if (currentStep === "review") return true
    
    const currentAnswer = answers[currentQuestion?.id]
    if (!currentAnswer) return false
    
    if (currentQuestion?.type === 'match') {
      return currentQuestion.subquestions.every((sq: any) => !!currentAnswer[sq.id])
    }
    
    return !!currentAnswer
  }

  const handleMatchChange = (subQId: number, value: string) => {
    if (currentStep === "review") return
    setAnswers(prev => ({
      ...prev,
      [currentQuestion.id]: {
        ...(prev[currentQuestion.id] || {}),
        [subQId]: value
      }
    }))
  }

  if (currentStep === "intro") {
    return (
      <Card className="max-w-2xl mx-auto border-primary/20 shadow-lg">
        <CardHeader className="text-center pb-2">
          <div className="h-16 w-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
            <Trophy className="h-8 w-8 text-primary" />
          </div>
          <CardTitle className="text-2xl">{quizData.name}</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4 text-center">
          <p className="text-muted-foreground leading-relaxed">
            {quizData.intro || "Este exame avaliará os teus conhecimentos sobre os módulos lidos."}
          </p>
          <div className="grid grid-cols-2 gap-4 pt-4">
            <div className="p-3 bg-muted rounded-lg">
              <p className="text-xs font-bold uppercase text-muted-foreground">Perguntas</p>
              <p className="text-xl font-bold">{questions.length}</p>
            </div>
            <div className="p-3 bg-muted rounded-lg">
              <p className="text-xs font-bold uppercase text-muted-foreground">Passagem</p>
              <p className="text-xl font-bold">15 / 20</p>
            </div>
          </div>
        </CardContent>
        <CardFooter>
          <Button onClick={handleStart} className="w-full font-bold h-12 text-lg shadow-md">
            Começar Exame
          </Button>
        </CardFooter>
      </Card>
    )
  }

  if (currentStep === "result") {
    const passed = score >= 15
    return (
      <Card className="max-w-2xl mx-auto border-primary/20 shadow-xl overflow-hidden">
        <div className={`h-2 ${passed ? "bg-green-500" : "bg-red-500"}`} />
        <CardContent className="pt-10 pb-8 text-center space-y-6">
          <div className="space-y-2">
            {passed ? (
              <>
                <div className="h-20 w-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4 scale-110">
                  <CheckCircle2 className="h-10 w-10" />
                </div>
                <h2 className="text-3xl font-black text-green-600 italic">APROVADO!</h2>
              </>
            ) : (
              <>
                <div className="h-20 w-20 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                  <XCircle className="h-10 w-10" />
                </div>
                <h2 className="text-3xl font-black text-red-600 italic">NÃO APROVADO</h2>
              </>
            )}
          </div>

          <div className="py-4">
            <p className="text-sm text-muted-foreground uppercase font-bold tracking-widest">A tua nota final</p>
            <div className="text-6xl font-black mt-2">
               {score.toFixed(1)} <span className="text-2xl text-muted-foreground">/ 20</span>
            </div>
          </div>

          <p className="text-muted-foreground px-8">
            {passed 
              ? "Parabéns! Concluíste este curso com sucesso. Podes agora emitir o teu certificado." 
              : "Infelizmente ainda não atingiste a nota mínima de 15 valores. Tenta rever os conteúdos e tenta novamente."}
          </p>
        </CardContent>
        <CardFooter className="bg-muted/30 p-6 flex flex-col gap-3">
          <div className="flex w-full gap-3">
            {!passed ? (
              <Button variant="outline" onClick={handleRestart} className="flex-1 gap-2 h-12 font-bold">
                <RotateCcw className="h-4 w-4" /> Tentar Novamente
              </Button>
            ) : (
              <Button variant="outline" onClick={handleStartReview} className="flex-1 gap-2 h-12 font-bold">
                 Rever Respostas
              </Button>
            )}
            <Button 
              onClick={passed ? onFinish : onReview} 
              className={`flex-1 h-12 font-bold ${passed ? "bg-green-600 hover:bg-green-700" : ""}`}
            >
               {passed ? "Continuar para Conclusão" : "Rever Conteúdos"}
            </Button>
          </div>
        </CardFooter>
      </Card>
    )
  }

  const progress = ((currentQuestionIndex + 1) / questions.length) * 100
  const isReviewMode = currentStep === "review"

  return (
    <div className="max-w-3xl mx-auto space-y-6">
      <div className="space-y-2">
        <div className="flex justify-between items-end text-sm font-bold">
           <span className={cn(
             "uppercase tracking-tighter",
             isReviewMode ? "text-amber-600" : "text-primary"
           )}>
             {isReviewMode ? "Modo de Revisão" : "Pergunta"} {currentQuestionIndex + 1} de {questions.length}
           </span>
           <span className="text-muted-foreground">{Math.round(progress)}%</span>
        </div>
        <Progress value={progress} className={cn("h-2", isReviewMode && "bg-amber-100")} />
      </div>

      <Card className={cn(
        "shadow-lg border-primary/10",
        isReviewMode && "border-amber-200"
      )}>
        <CardHeader className={cn(
          "border-b",
          isReviewMode ? "bg-amber-50/50" : "bg-muted/30"
        )}>
          <CardTitle className="text-xl font-semibold leading-snug">
            {currentQuestion.text}
          </CardTitle>
        </CardHeader>
        <CardContent className="pt-8">
          {currentQuestion.type === 'match' ? (
            <div className="space-y-4">
              {currentQuestion.subquestions.map((sq: any) => {
                const userAns = answers[currentQuestion.id]?.[sq.id]
                const isCorrect = userAns === sq.correct_answer
                
                return (
                  <div 
                    key={sq.id} 
                    className={cn(
                      "flex flex-col space-y-3 p-5 rounded-xl border",
                      isReviewMode 
                        ? isCorrect 
                          ? "bg-green-50 border-green-200" 
                          : "bg-red-50 border-red-200"
                        : "bg-muted/30 border-border/50"
                    )}
                  >
                    <div className="font-medium text-foreground leading-relaxed flex justify-between items-start">
                      <span>{sq.text}</span>
                      {isReviewMode && (
                        isCorrect ? <CheckCircle2 className="h-4 w-4 text-green-600 mt-1" /> : <XCircle className="h-4 w-4 text-red-600 mt-1" />
                      )}
                    </div>
                    <div className="w-full">
                      <Select 
                        onValueChange={(val) => handleMatchChange(sq.id, val)}
                        value={userAns || ""}
                        disabled={isReviewMode}
                      >
                        <SelectTrigger className={cn(
                          "bg-white min-h-[44px] h-auto py-2 text-left flex items-center justify-between whitespace-normal",
                          isReviewMode && !isCorrect && "border-red-300 ring-red-100"
                        )}>
                          <SelectValue placeholder="Escolha a associação correspondente..." />
                        </SelectTrigger>
                        <SelectContent className="max-w-[90vw]">
                          {currentQuestion.options.map((opt: any) => (
                            <SelectItem key={opt.id} value={opt.text} className="whitespace-normal py-3">
                              {opt.text}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                      {isReviewMode && !isCorrect && (
                        <p className="mt-2 text-xs font-bold text-green-700">
                          Resposta correta: <span className="underline">{sq.correct_answer}</span>
                        </p>
                      )}
                    </div>
                  </div>
                )
              })}
            </div>
          ) : (
            <RadioGroup 
              value={answers[currentQuestion.id]?.toString()} 
              onValueChange={(val) => {
                if (!isReviewMode) {
                  setAnswers(prev => ({ ...prev, [currentQuestion.id]: parseInt(val) }))
                }
              }}
              className="space-y-4"
              disabled={isReviewMode}
            >
              {currentQuestion.options.map((option: any) => {
                const isSelected = answers[currentQuestion.id] === option.id
                const isCorrect = option.is_correct
                
                let variantClass = "border-transparent bg-muted/50"
                if (isReviewMode) {
                  if (isCorrect) variantClass = "border-green-500 bg-green-50"
                  else if (isSelected && !isCorrect) variantClass = "border-red-500 bg-red-50"
                } else if (isSelected) {
                  variantClass = "border-primary bg-primary/5 shadow-sm"
                }

                return (
                  <div 
                    key={option.id}
                    className={cn(
                      "flex items-center space-x-3 p-4 rounded-xl border-2 transition-all cursor-pointer relative",
                      variantClass,
                      !isReviewMode && "hover:bg-muted"
                    )}
                    onClick={() => {
                      if (!isReviewMode) {
                        setAnswers(prev => ({ ...prev, [currentQuestion.id]: option.id }))
                      }
                    }}
                  >
                    <RadioGroupItem 
                      value={option.id.toString()} 
                      id={`opt-${option.id}`} 
                      className={cn(isReviewMode && isCorrect && "border-green-600 text-green-600")}
                    />
                    <Label htmlFor={`opt-${option.id}`} className="flex-1 cursor-pointer text-base font-medium flex justify-between items-center">
                      <span>{option.text}</span>
                      {isReviewMode && isSelected && (
                        isCorrect ? <CheckCircle2 className="h-5 w-5 text-green-600" /> : <XCircle className="h-5 w-5 text-red-600" />
                      )}
                      {isReviewMode && !isSelected && isCorrect && (
                        <span className="text-[10px] font-black text-green-600 uppercase italic">Opção correta</span>
                      )}
                    </Label>
                  </div>
                )
              })}
            </RadioGroup>
          )}
        </CardContent>
        <CardFooter className="border-t bg-muted/10 p-6 flex justify-between items-center">
          <div className="flex items-center gap-4">
            <Button
              variant="outline"
              size="sm"
              onClick={handleBack}
              disabled={currentQuestionIndex === 0 && !isReviewMode}
              className="gap-2 font-bold"
            >
              <ArrowLeft className="h-4 w-4" /> {currentQuestionIndex === 0 && isReviewMode ? "Voltar ao Resultado" : "Anterior"}
            </Button>
            <div className="flex items-center gap-2 text-muted-foreground">
               <AlertCircle className="h-4 w-4" />
               <span className="text-xs hidden sm:inline">
                 {isReviewMode ? "Estás em modo de consulta." : currentQuestion.type === 'match' ? "Associa todos os itens." : "Escolhe a opção correta."}
               </span>
            </div>
          </div>
          <Button 
            onClick={handleNext} 
            disabled={!isQuestionAnswered()} 
            className={cn(
              "min-w-[140px] font-bold gap-2 shadow-md",
              isReviewMode && "bg-amber-600 hover:bg-amber-700"
            )}
          >
            {isLastQuestion ? (isReviewMode ? "Sair da Revisão" : "Finalizar") : "Seguinte"} <ArrowRight className="h-4 w-4" />
          </Button>
        </CardFooter>
      </Card>
    </div>
  )
}
