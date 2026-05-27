"use client"

import { useState } from "react"
import { 
  Star, 
  Send, 
  CheckCircle2, 
  MessageSquare,
  Loader2,
  ChevronRight,
  ChevronLeft
} from "lucide-react"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from "@/components/ui/card"
import { Textarea } from "@/components/ui/textarea"
import { Label } from "@/components/ui/label"
import { Progress } from "@/components/ui/progress"
import { cn } from "@/lib/utils"

interface FeedbackItem {
  id: number
  name: string
  type: 'multichoice' | 'textarea'
  required: boolean
  options: string[]
}

interface FeedbackData {
  id: number
  name: string
  intro: string
  items: FeedbackItem[]
}

interface FeedbackEngineProps {
  cmid: number
  feedbackData: FeedbackData
  onComplete: (average: number, responses: any) => void
  onFinish?: () => void
}

export function FeedbackEngine({ cmid, feedbackData, onComplete, onFinish }: FeedbackEngineProps) {
  // Feedback State Machine
  // Manages the user journey through the evaluation survey:
  // intro -> sequential questions -> success/thank you screen.
  const [currentStep, setCurrentStep] = useState<"intro" | "questions" | "success">("intro")
  const [currentIndex, setCurrentIndex] = useState(0)
  const [responses, setResponses] = useState<Record<number, any>>({})
  const [isSubmitting, setIsSubmitting] = useState(false)

  const items = feedbackData.items || []
  const currentItem = items[currentIndex]
  const isLast = currentIndex === items.length - 1

  const handleStart = () => setCurrentStep("questions")

  const handleResponse = (itemId: number, value: any) => {
    setResponses(prev => ({ ...prev, [itemId]: value }))
  }

  // Statistical Evaluation Logic
  // Calculates the average rating from all 'multichoice' items to determine overall course satisfaction.
  // Assumes options are formatted with a leading number (e.g., "5 (Excelente)").
  const calculateAverage = () => {
    const mcItems = items.filter(i => i.type === 'multichoice')
    if (mcItems.length === 0) return 0
    
    let total = 0
    let count = 0
    
    mcItems.forEach(item => {
      const val = responses[item.id]
      if (val) {
        // Extract numeric rating from option string
        const num = parseInt(val.toString().charAt(0))
        if (!isNaN(num)) {
          total += num
          count++
        }
      }
    })
    
    return count > 0 ? total / count : 0
  }

  // Form Submission
  // Compiles the user's responses into the format expected by the Moodle Feedback WebService
  // and dispatches them via the intermediate Next.js API route.
  const handleSubmit = async () => {
    setIsSubmitting(true)
    const average = calculateAverage()
    
    // Format responses for Moodle API consumption
    const formattedResponses = Object.entries(responses).map(([itemId, value]) => ({
      itemid: parseInt(itemId),
      value: value.toString()
    }))

    try {
      const response = await fetch('/api/feedback/submit', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
          cmid, 
          responses: formattedResponses 
        })
      })

      if (!response.ok) throw new Error("Erro ao submeter feedback")
      
      setCurrentStep("success")
      onComplete(average, responses)

      // Automatic progression to next activity/certificate download
      if (onFinish) {
        setTimeout(onFinish, 2500)
      }
    } catch (err) {
      console.error("Submission error:", err)
    } finally {
      setIsSubmitting(false)
    }
  }

  // Validation Logic
  // Enforces mandatory fields before allowing progression to the next survey item.
  const canGoNext = () => {
    if (!currentItem) return false
    if (!currentItem.required) return true
    return !!responses[currentItem.id]
  }

  if (currentStep === "intro") {
    return (
      <Card className="max-w-2xl mx-auto border-primary/20 shadow-lg">
        <CardHeader className="text-center pb-2">
          <div className="h-16 w-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
            <MessageSquare className="h-8 w-8 text-primary" />
          </div>
          <CardTitle className="text-2xl">{feedbackData.name}</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4 text-center">
          <p className="text-muted-foreground leading-relaxed">
            {feedbackData.intro || "A sua opinião é fundamental para melhorarmos a qualidade das nossas formações."}
          </p>
          <div className="p-4 bg-muted rounded-lg inline-block mx-auto">
            <p className="text-xs font-bold uppercase text-muted-foreground">Tempo Estimado</p>
            <p className="text-lg font-bold">~2 minutos</p>
          </div>
        </CardContent>
        <CardFooter>
          <Button onClick={handleStart} className="w-full font-bold h-12 text-lg shadow-md">
            Iniciar Avaliação
          </Button>
        </CardFooter>
      </Card>
    )
  }

  if (currentStep === "success") {
    const avg = calculateAverage()
    return (
      <Card className="max-w-2xl mx-auto border-primary/20 shadow-xl overflow-hidden">
        <div className="h-2 bg-green-500" />
        <CardContent className="pt-10 pb-8 text-center space-y-6">
          <div className="h-20 w-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
            <CheckCircle2 className="h-10 w-10" />
          </div>
          <h2 className="text-3xl font-black text-green-600 italic">OBRIGADO PELO FEEDBACK!</h2>
          
          <div className="py-4 bg-primary/5 rounded-2xl border border-primary/10 max-w-sm mx-auto">
            <p className="text-xs text-muted-foreground uppercase font-bold tracking-widest">A tua avaliação média</p>
            <div className="flex items-center justify-center gap-2 mt-2">
               <span className="text-4xl font-black text-primary">{avg.toFixed(1)}</span>
               <div className="flex">
                  {[1, 2, 3, 4, 5].map((s) => (
                    <Star 
                      key={s} 
                      className={cn(
                        "h-5 w-5", 
                        s <= Math.round(avg) ? "text-amber-400 fill-amber-400" : "text-muted-foreground"
                      )} 
                    />
                  ))}
               </div>
            </div>
          </div>

          <p className="text-muted-foreground px-8">
            As tuas respostas foram registadas com sucesso. A redirecionar para a conclusão do curso...
          </p>
        </CardContent>
      </Card>
    )
  }

  const progress = ((currentIndex + 1) / items.length) * 100

  return (
    <div className="max-w-3xl mx-auto space-y-6">
      <div className="space-y-2">
        <div className="flex justify-between items-end text-sm font-bold">
           <span className="text-primary uppercase tracking-tighter">Avaliação: Pergunta {currentIndex + 1} de {items.length}</span>
           <span className="text-muted-foreground">{Math.round(progress)}%</span>
        </div>
        <Progress value={progress} className="h-2" />
      </div>

      <Card className="shadow-lg border-primary/10">
        <CardHeader className="bg-muted/30 border-b min-h-[100px] flex items-center justify-center text-center">
          <CardTitle className="text-xl font-semibold leading-snug">
            {currentItem?.name || "Questão sem título"}
          </CardTitle>
        </CardHeader>
        <CardContent className="pt-12 pb-12">
          {currentItem?.type === 'multichoice' ? (
            <div className="flex flex-col items-center space-y-8">
              <div className="flex gap-4">
                {[1, 2, 3, 4, 5].map((star) => {
                  const isSelected = responses[currentItem.id] && parseInt(responses[currentItem.id].toString().charAt(0)) >= star
                  const isExactly = responses[currentItem.id] && parseInt(responses[currentItem.id].toString().charAt(0)) === star
                  
                  return (
                    <button
                      key={star}
                      onClick={() => handleResponse(currentItem.id, currentItem.options[star-1])}
                      className="group relative focus:outline-none transition-transform active:scale-95"
                    >
                      <Star 
                        className={cn(
                          "h-12 w-12 transition-all duration-300",
                          isSelected ? "text-amber-400 fill-amber-400 scale-110" : "text-muted-foreground hover:text-amber-200",
                          isExactly && "drop-shadow-[0_0_8px_rgba(251,191,36,0.5)]"
                        )}
                      />
                      <span className="absolute -bottom-6 left-1/2 -translate-x-1/2 text-[10px] font-bold text-muted-foreground opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                        {star} Estrelas
                      </span>
                    </button>
                  )
                })}
              </div>
              
              {responses[currentItem.id] && (
                <p className="text-primary font-black uppercase italic tracking-wider animate-in fade-in slide-in-from-top-2">
                  {responses[currentItem.id]}
                </p>
              )}
            </div>
          ) : currentItem?.type === 'textarea' ? (
            <div className="space-y-4">
              <Label className="text-muted-foreground">A tua resposta (opcional):</Label>
              <Textarea 
                placeholder="Escreve aqui os teus comentários..."
                className="min-h-[150px] text-base resize-none focus:ring-primary/20"
                value={responses[currentItem.id] || ""}
                onChange={(e) => handleResponse(currentItem.id, e.target.value)}
              />
            </div>
          ) : (
            <div className="text-center py-10">
               <p className="text-muted-foreground italic">Tipo de questão não suportado ou erro no carregamento.</p>
            </div>
          )}
        </CardContent>
        <CardFooter className="border-t bg-muted/10 p-6 flex justify-between items-center">
          <Button
            variant="outline"
            size="lg"
            onClick={() => setCurrentIndex(prev => prev - 1)}
            disabled={currentIndex === 0}
            className="gap-2 font-bold"
          >
            <ChevronLeft className="h-4 w-4" /> Anterior
          </Button>

          {isLast ? (
            <Button 
              onClick={handleSubmit} 
              disabled={!canGoNext() || isSubmitting} 
              className="min-w-[160px] font-bold gap-2 shadow-md bg-green-600 hover:bg-green-700 h-12"
            >
              {isSubmitting ? <Loader2 className="h-5 w-5 animate-spin" /> : <Send className="h-5 w-5" />}
              Submeter Avaliação
            </Button>
          ) : (
            <Button 
              onClick={() => setCurrentIndex(prev => prev + 1)} 
              disabled={!canGoNext()} 
              className="min-w-[140px] font-bold gap-2 shadow-md h-12"
            >
              Próximo <ChevronRight className="h-4 w-4" />
            </Button>
          )}
        </CardFooter>
      </Card>
    </div>
  )
}
