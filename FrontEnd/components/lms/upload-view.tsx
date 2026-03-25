"use client"

import { useState, useCallback, useEffect } from "react"
import { Upload, FileText, X, CheckCircle2, Loader2, Video, FileQuestion, Award, BookOpen, AlertCircle, Eye, Copy, ClipboardCopy, Factory, Sparkles} from "lucide-react"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Progress } from "@/components/ui/progress"
import { Checkbox } from "@/components/ui/checkbox"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { Textarea } from "@/components/ui/textarea"
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/components/ui/accordion"
import { useToast } from "@/hooks/use-toast"
import { cn } from "@/lib/utils"
import type { GenerationConfig, MoodleCourse, GenerationProgress } from "@/lib/types"
import { CoursePreview } from "./course-preview"

interface UploadedFile {
  id: string
  name: string
  size: string
  file: File
  status: "uploading" | "processing" | "ready" | "error"
  progress: number
  content?: string
}

const generationOptions = [
  { id: "videos", label: "Gerar Vídeos Explicativos", icon: Video, description: "Cria vídeos com narração AI a partir do conteúdo" },
  { id: "quizzes", label: "Gerar Exames e Quizzes", icon: FileQuestion, description: "Cria perguntas de avaliação automáticas" },
  { id: "certificate", label: "Certificação Automática", icon: Award, description: "Emite certificado ao completar o curso" },
  { id: "modules", label: "Dividir em Módulos", icon: BookOpen, description: "Organiza o conteúdo em módulos lógicos" },
]

export function UploadView() {
  const { toast } = useToast()
  const [files, setFiles] = useState<UploadedFile[]>([])
  const [courseName, setCourseName] = useState("")
  const [difficulty, setDifficulty] = useState<"easy" | "medium" | "hard">("medium")
  const [depth, setDepth] = useState<"flash" | "standard" | "deep">("standard")
  const [quizDuration, setQuizDuration] = useState(30)
  const [numberOfQuestions, setNumberOfQuestions] = useState(20)
  const [selectedOptions, setSelectedOptions] = useState<string[]>(["quizzes", "certificate", "modules"])
  const [isDragOver, setIsDragOver] = useState(false)
  
  // States for Factory tab
  const [masterPrompt, setMasterPrompt] = useState("")
  const [dynamicPrompt, setDynamicPrompt] = useState("")
  
  const [generationState, setGenerationState] = useState<GenerationProgress>({
    status: "idle",
    progress: 0,
    message: ""
  })
  const [generatedCourse, setGeneratedCourse] = useState<MoodleCourse | null>(null)
  const [showPreview, setShowPreview] = useState(false)
  const [moodleStatus, setMoodleStatus] = useState<"checking" | "connected" | "disconnected" | "error">("checking")
  const [moodleInfo, setMoodleInfo] = useState<{ siteName?: string; username?: string } | null>(null)

  const checkMoodleConnection = useCallback(async () => {
    setMoodleStatus("checking")
    try {
      const response = await fetch("/api/send-to-moodle")
      const data = await response.json()
      if (data.connected) {
        setMoodleStatus("connected")
        setMoodleInfo({ siteName: data.siteName, username: data.username })
      } else {
        setMoodleStatus("disconnected")
      }
    } catch {
      setMoodleStatus("error")
    }
  }, [])

  // Fetch master prompt on mount
  useEffect(() => {
    async function fetchMasterPrompt() {
      try {
        const response = await fetch("/api/master-prompt")
        const data = await response.json()
        if (data.content) {
          setMasterPrompt(data.content)
        }
      } catch (error) {
        console.error("Error fetching master prompt:", error)
      }
    }
    fetchMasterPrompt()
    checkMoodleConnection()
  }, [checkMoodleConnection])

  // Update dynamic prompt when options change
  useEffect(() => {
    if (!masterPrompt) return

    const depthText = (depth === 'flash') ? "RESUMO EXECUTIVO (1-2h). Foco apenas nos pontos chave, linguagem direta, eliminando detalhes secundários." : 
                    (depth === 'deep') ? "DEEP DIVE EXAUSTIVO (8-10h). Não resumas NADA. Expande cada tópico com explicações minuciosas e secções de aprofundamento." : 
                    "ESTRUTURA STANDARD (3-4h). Equilibrado, preservando todo o detalhe técnico sem expansão excessiva.";
                    
    const diffText = (difficulty === 'easy') ? "FÁCIL. Perguntas diretas de verificação de leitura básica." : 
                   (difficulty === 'hard') ? "DIFÍCIL. Perguntas complexas baseadas em análise de cenários e pensamento crítico." : 
                   "MÉDIA. Aplicação prática dos conceitos.";

    let header = `###################################################\n`;
    header += `# ORDENS DE PRODUÇÃO DINÂMICAS:\n`;
    header += `# PROFUNDIDADE: ${depthText}\n`;
    header += `# DIFICULDADE QUIZ: ${diffText}\n`;
    header += `###################################################\n\n`;

    setDynamicPrompt(header + masterPrompt)
  }, [depth, difficulty, masterPrompt])

  const extractTextFromPDF = async (file: File): Promise<string> => {
    // For now, we'll read the file and send it to be processed
    // In production, you'd use pdf-parse or similar library
    return new Promise((resolve) => {
      const reader = new FileReader()
      reader.onload = async (e) => {
        const text = e.target?.result as string
        // This is a placeholder - in production you'd extract text properly
        // For now, we'll use the filename as a reference
        resolve(`[Conteudo do ficheiro: ${file.name}]\n\nPor favor, analise o manual anexado e crie um curso completo.`)
      }
      reader.readAsText(file)
    })
  }

  const handleDrop = useCallback(async (e: React.DragEvent) => {
    e.preventDefault()
    setIsDragOver(false)
    
    const droppedFiles = Array.from(e.dataTransfer.files).filter(
      file => file.type === "application/pdf"
    )
    
    for (const file of droppedFiles) {
      const newFile: UploadedFile = {
        id: `${Date.now()}-${Math.random()}`,
        name: file.name,
        size: `${(file.size / 1024 / 1024).toFixed(2)} MB`,
        file: file,
        status: "processing",
        progress: 50
      }
      
      setFiles(prev => [...prev, newFile])
      
      // Extract text content
      const content = await extractTextFromPDF(file)
      
      setFiles(prev => prev.map(f => 
        f.id === newFile.id 
          ? { ...f, status: "ready" as const, progress: 100, content }
          : f
      ))
    }
  }, [])

  const handleFileInput = async (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files) {
      const selectedFiles = Array.from(e.target.files).filter(
        file => file.type === "application/pdf"
      )
      
      for (const file of selectedFiles) {
        const newFile: UploadedFile = {
          id: `${Date.now()}-${Math.random()}`,
          name: file.name,
          size: `${(file.size / 1024 / 1024).toFixed(2)} MB`,
          file: file,
          status: "processing",
          progress: 50
        }
        
        setFiles(prev => [...prev, newFile])
        
        const content = await extractTextFromPDF(file)
        
        setFiles(prev => prev.map(f => 
          f.id === newFile.id 
            ? { ...f, status: "ready" as const, progress: 100, content }
            : f
        ))
      }
    }
  }

  const removeFile = (id: string) => {
    setFiles(prev => prev.filter(f => f.id !== id))
  }

  const toggleOption = (id: string) => {
    setSelectedOptions(prev => 
      prev.includes(id) ? prev.filter(o => o !== id) : [...prev, id]
    )
  }

  const handleCopyPrompt = async () => {
    if (files.length === 0 || !courseName) {
      toast({
        title: "Dados insuficientes",
        description: "Adicione um PDF e defina o nome do curso para gerar o prompt.",
        variant: "destructive"
      })
      return
    }

    setIsCopyingPrompt(true)
    try {
      const config: GenerationConfig = {
        courseName,
        difficulty,
        depth,
        quizDuration,
        numberOfQuestions,
        generateVideos: selectedOptions.includes("videos"),
        generateQuizzes: selectedOptions.includes("quizzes"),
        generateCertificate: selectedOptions.includes("certificate"),
        divideInModules: selectedOptions.includes("modules")
      }

      const formData = new FormData()
      files.forEach(f => formData.append("files", f.file))
      formData.append("config", JSON.stringify(config))

      const response = await fetch("/api/get-prompt", {
        method: "POST",
        body: formData
      })

      if (!response.ok) throw new Error("Erro ao gerar prompt")
      
      const { prompt } = await response.json()
      await navigator.clipboard.writeText(prompt)
      
      toast({
        title: "Prompt Copiado!",
        description: "O prompt foi copiado para a área de transferência.",
      })
    } catch (error) {
      toast({
        title: "Erro",
        description: error instanceof Error ? error.message : "Erro desconhecido",
        variant: "destructive"
      })
    } finally {
      setIsCopyingPrompt(false)
    }
  }

  const handleJsonUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (!file) return

    const reader = new FileReader()
    reader.onload = (event) => {
      try {
        const json = JSON.parse(event.target?.result as string)
        // Basic validation
        if (json.course_name && json.activities) {
          setGeneratedCourse(json)
          setShowPreview(true)
          toast({
            title: "JSON Carregado",
            description: "O curso foi carregado com sucesso.",
          })
        } else {
          throw new Error("Formato de curso inválido")
        }
      } catch (error) {
        toast({
          title: "Erro no JSON",
          description: "O ficheiro não é um JSON de curso válido.",
          variant: "destructive"
        })
      }
    }
    reader.readAsText(file)
  }

  const handleGenerate = async () => {
    if (files.length === 0 || !courseName) return
    
    const config: GenerationConfig = {
      courseName,
      difficulty,
      depth,
      quizDuration,
      numberOfQuestions,
      generateVideos: selectedOptions.includes("videos"),
      generateQuizzes: selectedOptions.includes("quizzes"),
      generateCertificate: selectedOptions.includes("certificate"),
      divideInModules: selectedOptions.includes("modules")
    }

    setGenerationState({
      status: "extracting",
      progress: 10,
      message: "A extrair conteudo do PDF..."
    })

    try {
      // Combine content from all files
      const pdfContent = files
        .map(f => f.content || "")
        .join("\n\n---\n\n")

      setGenerationState({
        status: "generating",
        progress: 30,
        message: "A gerar curso com IA (Gemini)..."
      })

      // Send the actual files and config
      const formData = new FormData()
      files.forEach(f => {
        formData.append("files", f.file)
      })
      formData.append("config", JSON.stringify(config))
      
      // Enviar o prompt dinâmico sincronizado com a Fábrica de Cursos
      if (dynamicPrompt) {
        formData.append("customPrompt", dynamicPrompt)
      }

      const response = await fetch("/api/generate-course", {
        method: "POST",
        body: formData
      })

      if (!response.ok) {
        const error = await response.json()
        throw new Error(error.error || "Failed to generate course")
      }

      const data = await response.json()
      
      setGenerationState({
        status: "complete",
        progress: 100,
        message: "Curso gerado com sucesso!",
        course: data.course
      })
      
      setGeneratedCourse(data.course)
      setShowPreview(true)

    } catch (error) {
      setGenerationState({
        status: "error",
        progress: 0,
        message: "Erro ao gerar curso",
        error: error instanceof Error ? error.message : "Unknown error"
      })
    }
  }

  const handleSendToMoodle = async () => {
    if (!generatedCourse) return

    setGenerationState({
      status: "sending",
      progress: 80,
      message: "A enviar para o Moodle..."
    })

    try {
      const response = await fetch("/api/send-to-moodle", {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify({ course: generatedCourse })
      })

      const data = await response.json()

      if (!response.ok) {
        throw new Error(data.error || "Failed to send to Moodle")
      }

      setGenerationState({
        status: "complete",
        progress: 100,
        message: `Curso criado no Moodle com sucesso! (ID: ${data.courseId})`
      })

      // Reset form after successful creation
      setTimeout(() => {
        setFiles([])
        setCourseName("")
        setGeneratedCourse(null)
        setShowPreview(false)
        setGenerationState({ status: "idle", progress: 0, message: "" })
      }, 3000)

    } catch (error) {
      setGenerationState({
        status: "error",
        progress: 0,
        message: "Erro ao enviar para o Moodle",
        error: error instanceof Error ? error.message : "Unknown error"
      })
    }
  }

  const isGenerating = generationState.status === "extracting" || 
                       generationState.status === "generating" || 
                       generationState.status === "sending"

  if (showPreview && generatedCourse) {
    return (
      <CoursePreview 
        course={generatedCourse}
        onBack={() => setShowPreview(false)}
        onSendToMoodle={handleSendToMoodle}
        isSending={generationState.status === "sending"}
        moodleConnected={moodleStatus === "connected"}
      />
    )
  }

  return (
    <div className="space-y-6 max-w-4xl">
      <div>
        <h1 className="text-2xl font-bold text-foreground">Gerar Novo Curso</h1>
        <p className="text-muted-foreground">Escolha entre gerar um curso via IA ou carregar um JSON pré-existente</p>
      </div>

      <Tabs defaultValue="ai" className="w-full">
        <TabsList className="grid w-full grid-cols-2">
          <TabsTrigger value="ai" className="gap-2">
            <Sparkles className="h-4 w-4" />
            Gerar com IA (PDF)
          </TabsTrigger>
          <TabsTrigger value="json" className="gap-2">
            <Factory className="h-4 w-4" />
            Fábrica de cursos
          </TabsTrigger>
        </TabsList>

        <TabsContent value="ai" className="space-y-6 mt-6">
          {/* Moodle Connection Status */}
          <Card className={cn(
            "border-2",
            moodleStatus === "connected" && "border-green-500 bg-green-50",
            moodleStatus === "disconnected" && "border-amber-500 bg-amber-50",
            moodleStatus === "error" && "border-red-500 bg-red-50",
            moodleStatus === "checking" && "border-muted"
          )}>
            <CardContent className="p-4">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  {moodleStatus === "checking" && <Loader2 className="h-5 w-5 animate-spin text-muted-foreground" />}
                  {moodleStatus === "connected" && <CheckCircle2 className="h-5 w-5 text-green-600" />}
                  {moodleStatus === "disconnected" && <AlertCircle className="h-5 w-5 text-amber-600" />}
                  {moodleStatus === "error" && <AlertCircle className="h-5 w-5 text-red-600" />}
                  <div>
                    <p className="font-medium text-foreground">
                      {moodleStatus === "checking" && "A verificar conexao ao Moodle..."}
                      {moodleStatus === "connected" && `Conectado ao Moodle: ${moodleInfo?.siteName}`}
                      {moodleStatus === "disconnected" && "Moodle desconectado"}
                      {moodleStatus === "error" && "Erro ao conectar ao Moodle"}
                    </p>
                    {moodleStatus === "connected" && moodleInfo?.username && (
                      <p className="text-sm text-muted-foreground">Utilizador: {moodleInfo.username}</p>
                    )}
                    {(moodleStatus === "disconnected" || moodleStatus === "error") && (
                      <p className="text-sm text-muted-foreground">Verifique as variaveis MOODLE_URL e MOODLE_TOKEN</p>
                    )}
                  </div>
                </div>
                <Button variant="outline" size="sm" onClick={checkMoodleConnection}>
                  Testar Conexao
                </Button>
              </div>
            </CardContent>
          </Card>

          {/* Upload Area */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg font-semibold">
                <Upload className="h-5 w-5 text-primary" />
                Upload de Manuais PDF
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div
                onDrop={handleDrop}
                onDragOver={(e) => { e.preventDefault(); setIsDragOver(true) }}
                onDragLeave={() => setIsDragOver(false)}
                className={cn(
                  "border-2 border-dashed rounded-lg p-8 text-center transition-colors",
                  isDragOver ? "border-primary bg-primary/5" : "border-border",
                  "hover:border-primary/50"
                )}
              >
                <div className="flex flex-col items-center gap-4">
                  <div className="h-16 w-16 rounded-full bg-primary/10 flex items-center justify-center">
                    <Upload className="h-8 w-8 text-primary" />
                  </div>
                  <div>
                    <p className="text-lg font-medium text-foreground">Arraste os ficheiros PDF aqui</p>
                    <p className="text-sm text-muted-foreground mt-1">ou clique para selecionar</p>
                  </div>
                  <input
                    type="file"
                    accept=".pdf"
                    multiple
                    onChange={handleFileInput}
                    className="hidden"
                    id="file-upload"
                    disabled={isGenerating}
                  />
                  <Button asChild variant="outline" disabled={isGenerating}>
                    <label htmlFor="file-upload" className="cursor-pointer">
                      Selecionar Ficheiros
                    </label>
                  </Button>
                </div>
              </div>

              {/* Uploaded Files List */}
              {files.length > 0 && (
                <div className="mt-6 space-y-3">
                  <h4 className="font-medium text-foreground">Ficheiros Carregados</h4>
                  {files.map((file) => (
                    <div key={file.id} className="flex items-center gap-4 p-3 rounded-lg bg-muted">
                      <FileText className="h-8 w-8 text-primary" />
                      <div className="flex-1 min-w-0">
                        <p className="font-medium text-foreground truncate">{file.name}</p>
                        <p className="text-sm text-muted-foreground">{file.size}</p>
                      </div>
                      <div className="flex items-center gap-2">
                        {file.status === "ready" && (
                          <CheckCircle2 className="h-5 w-5 text-green-500" />
                        )}
                        {file.status === "processing" && (
                          <Loader2 className="h-5 w-5 text-primary animate-spin" />
                        )}
                        <Button 
                          variant="ghost" 
                          size="icon" 
                          onClick={() => removeFile(file.id)}
                          disabled={isGenerating}
                        >
                          <X className="h-4 w-4" />
                        </Button>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>

          {/* Course Settings */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg font-semibold">
                <Sparkles className="h-5 w-5 text-primary" />
                Configuracoes do Curso
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="space-y-2">
                <Label htmlFor="course-name">Nome do Curso</Label>
                <Input
                  id="course-name"
                  placeholder="Ex: Seguranca no Trabalho - Normas ISO 45001"
                  value={courseName}
                  onChange={(e) => setCourseName(e.target.value)}
                  disabled={isGenerating}
                />
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div className="space-y-2">
                  <Label>Profundidade</Label>
                  <Select value={depth} onValueChange={(v) => setDepth(v as typeof depth)} disabled={isGenerating}>
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="flash">Flash (1-2h)</SelectItem>
                      <SelectItem value="standard">Standard (3-4h)</SelectItem>
                      <SelectItem value="deep">Deep Dive (8-10h)</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div className="space-y-2">
                  <Label>Dificuldade Quiz</Label>
                  <Select value={difficulty} onValueChange={(v) => setDifficulty(v as typeof difficulty)} disabled={isGenerating}>
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="easy">Facil</SelectItem>
                      <SelectItem value="medium">Media</SelectItem>
                      <SelectItem value="hard">Dificil</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="quiz-duration">Duracao do Quiz (min)</Label>
                  <Input
                    id="quiz-duration"
                    type="number"
                    min={5}
                    max={120}
                    value={quizDuration}
                    onChange={(e) => setQuizDuration(Number(e.target.value))}
                    disabled={isGenerating}
                  />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="num-questions">Numero de Questoes</Label>
                  <Input
                    id="num-questions"
                    type="number"
                    min={5}
                    max={50}
                    value={numberOfQuestions}
                    onChange={(e) => setNumberOfQuestions(Number(e.target.value))}
                    disabled={isGenerating}
                  />
                </div>
              </div>

              <div className="space-y-4">
                <Label>Opcoes de Geracao</Label>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  {generationOptions.map((option) => {
                    const Icon = option.icon
                    const isSelected = selectedOptions.includes(option.id)
                    const isDisabled = isGenerating || option.id === "videos" // Videos disabled for now
                    return (
                      <div
                        key={option.id}
                        onClick={() => !isDisabled && toggleOption(option.id)}
                        className={cn(
                          "flex items-start gap-3 p-4 rounded-lg border cursor-pointer transition-colors",
                          isSelected ? "border-primary bg-primary/5" : "border-border hover:border-primary/50",
                          isDisabled && "opacity-50 cursor-not-allowed"
                        )}
                      >
                        <Checkbox 
                          checked={isSelected} 
                          disabled={isDisabled}
                          className="mt-0.5"
                        />
                        <div className="flex-1">
                          <div className="flex items-center gap-2">
                            <Icon className="h-4 w-4 text-primary" />
                            <span className="font-medium text-foreground">{option.label}</span>
                            {option.id === "videos" && (
                              <span className="text-xs bg-muted px-2 py-0.5 rounded">Em breve</span>
                            )}
                          </div>
                          <p className="text-sm text-muted-foreground mt-1">{option.description}</p>
                        </div>
                      </div>
                    )
                  })}
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Prompt Preview Accordion */}
          <Accordion type="single" collapsible className="w-full">
            <AccordionItem value="prompt-preview" className="border rounded-lg px-4 bg-muted/20">
              <AccordionTrigger className="hover:no-underline font-medium text-sm text-primary">
                Ver Prompt Master (Dinâmico) que será enviado
              </AccordionTrigger>
              <AccordionContent className="pt-2">
                <Textarea 
                  value={dynamicPrompt || "A carregar prompt master..."} 
                  readOnly 
                  className="font-mono text-[10px] h-[200px] bg-white cursor-text" 
                />
                <p className="text-[10px] text-muted-foreground mt-2">
                  * Este prompt é sincronizado com as configurações de profundidade e dificuldade selecionadas acima.
                </p>
              </AccordionContent>
            </AccordionItem>
          </Accordion>

          {/* Generation Progress */}
          {(isGenerating || generationState.status === "error" || generationState.status === "complete") && (
            <Card className={cn(
              generationState.status === "error" && "border-red-500",
              generationState.status === "complete" && "border-green-500"
            )}>
              <CardContent className="p-6">
                <div className="flex items-center gap-4 mb-4">
                  {isGenerating && <Loader2 className="h-6 w-6 text-primary animate-spin" />}
                  {generationState.status === "error" && <AlertCircle className="h-6 w-6 text-red-500" />}
                  {generationState.status === "complete" && <CheckCircle2 className="h-6 w-6 text-green-500" />}
                  <div>
                    <p className="font-medium text-foreground">{generationState.message}</p>
                    {generationState.error && (
                      <p className="text-sm text-red-500 mt-1">{generationState.error}</p>
                    )}
                  </div>
                </div>
                {isGenerating && (
                  <>
                    <Progress value={generationState.progress} className="h-2" />
                    <p className="text-sm text-muted-foreground mt-2 text-right">{generationState.progress}%</p>
                  </>
                )}
              </CardContent>
            </Card>
          )}

          {/* Generate Buttons */}
          <div className="flex justify-between gap-3">
            
            <div className="flex gap-3">
              {generatedCourse && !isGenerating && (
                <Button 
                  variant="outline"
                  size="lg" 
                  onClick={() => setShowPreview(true)}
                  className="gap-2"
                >
                  <Eye className="h-5 w-5" />
                  Ver Preview
                </Button>
              )}
              <Button 
                size="lg" 
                onClick={handleGenerate}
                disabled={files.length === 0 || !courseName || isGenerating}
                className="gap-2"
              >
                {isGenerating ? (
                  <>
                    <Loader2 className="h-5 w-5 animate-spin" />
                    A Gerar...
                  </>
                ) : (
                  <>
                    <Sparkles className="h-5 w-5" />
                    Gerar Curso com IA
                  </>
                )}
              </Button>
            </div>
          </div>
        </TabsContent>

        <TabsContent value="json" className="mt-6 space-y-6">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Factory className="h-5 w-5 text-primary" />
                Configurador de Prompt Master
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="space-y-2">
                  <Label className="font-bold">⏱️ Duração / Profundidade:</Label>
                  <Select value={depth} onValueChange={(v) => setDepth(v as any)}>
                    <SelectTrigger className="w-full">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="flash">Flash (1-2 horas) - Essencial e Direto</SelectItem>
                      <SelectItem value="standard">Standard (3-4 horas) - Equilibrado</SelectItem>
                      <SelectItem value="deep">Deep Dive (8-10 horas) - Exaustivo e Académico</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label className="font-bold">Dificuldade do Quiz:</Label>
                  <Select value={difficulty} onValueChange={(v) => setDifficulty(v as any)}>
                    <SelectTrigger className="w-full">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="easy">Fácil - Verificação de conceitos básicos</SelectItem>
                      <SelectItem value="medium">Média - Aplicação prática e análise</SelectItem>
                      <SelectItem value="hard">Difícil - Casos complexos e pensamento crítico</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>

              <div className="space-y-4 pt-4 border-t border-border bg-muted/10 p-4 rounded-lg">
                <div className="flex items-center justify-between">
                  <h4 className="font-medium text-primary flex items-center gap-2">
                    <ClipboardCopy className="h-4 w-4" />
                    Passo 1: Copie o Prompt Master Integral
                  </h4>
                  <Button 
                    variant="default" 
                    size="sm" 
                    onClick={async () => {
                      await navigator.clipboard.writeText(dynamicPrompt)
                      toast({
                        title: "Prompt Copiado!",
                        description: "O prompt master foi copiado para a área de transferência.",
                      })
                    }}
                    className="gap-2 bg-primary hover:bg-primary/90"
                  >
                    <Copy className="h-4 w-4" />
                    Copiar Prompt Completo
                  </Button>
                </div>
                <p className="text-sm text-muted-foreground">
                  Este prompt é carregado diretamente do ficheiro mestre e atualizado dinamicamente.
                </p>
                <Textarea 
                  value={dynamicPrompt} 
                  readOnly 
                  className="font-mono text-[10px] h-[300px] bg-white border-muted cursor-text" 
                />
              </div>

              <div className="space-y-4 pt-6 border-t border-border bg-blue-50/30 p-4 rounded-lg">
                <h4 className="font-medium text-blue-700 flex items-center gap-2">
                  <Upload className="h-4 w-4" />
                  Passo 2: Upload do JSON Gerado
                </h4>
                <div className="grid w-full items-center gap-1.5">
                  <Input
                    id="factory-json-upload"
                    type="file"
                    accept=".json"
                    onChange={handleJsonUpload}
                    className="cursor-pointer bg-white border-blue-200"
                  />
                  <p className="text-xs text-muted-foreground">
                    Após usar o prompt acima na IA, descarregue o resultado em .json e carregue-o aqui para criar o curso.
                  </p>
                </div>
              </div>
              
              <Button 
                className="w-full py-8 text-lg font-bold shadow-lg"
                disabled={!generatedCourse}
                onClick={() => setShowPreview(true)}
              >
                Visualizar e Criar Curso no Moodle
              </Button>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  )
}
