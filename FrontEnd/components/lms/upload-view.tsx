"use client"

import { useState, useCallback, useEffect } from "react"
import { Upload, FileText, X, CheckCircle2, Loader2, Video, FileQuestion, Award, BookOpen, AlertCircle, Eye, Copy, ClipboardCopy, Factory, Sparkles, ExternalLink} from "lucide-react"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Badge } from "@/components/ui/badge"
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
]

export function UploadView() {
  const { toast } = useToast()
  const [files, setFiles] = useState<UploadedFile[]>([])
  const [courseName, setCourseName] = useState("")
  const [difficulty, setDifficulty] = useState<"easy" | "medium" | "hard">("medium")
  const [depth, setDepth] = useState<"Resumo Executivo" | "Profissional" | "Especialista Técnico">("Profissional")
  const [quizDuration, setQuizDuration] = useState(30)
  const [numberOfQuestions, setNumberOfQuestions] = useState(20)
  const [selectedOptions, setSelectedOptions] = useState<string[]>(["quizzes", "certificate"])
  const [isDragOver, setIsDragOver] = useState(false)
  const [isCopyingPrompt, setIsCopyingPrompt] = useState(false)
  const [copiedPrompt, setCopiedPrompt] = useState(false)
  
  // States for Factory tab
  const [masterPrompt, setMasterPrompt] = useState("")
  const [dynamicPrompt, setDynamicPrompt] = useState("")
  const [factoryPdfFile, setFactoryPdfFile] = useState<File | null>(null)
  
  const [generationState, setGenerationState] = useState<GenerationProgress>({
    status: "idle",
    progress: 0,
    message: ""
  })
  const [createdCourseId, setCreatedCourseId] = useState<number | null>(null)
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

    // Substituir placeholders {{DURATION}}, {{DIFFICULTY}}, {{NUM_QUESTIONS}}, {{BANK_SIZE}}
    const bankSize = numberOfQuestions + 10;
    
    let finalPrompt = masterPrompt
      .replace(/{{DURATION}}/g, depth)
      .replace(/{{DIFFICULTY}}/g, difficulty)
      .replace(/{{NUM_QUESTIONS}}/g, numberOfQuestions.toString())
      .replace(/{{BANK_SIZE}}/g, bankSize.toString())
      .replace(/{{QUIZ_DURATION}}/g, quizDuration.toString())
      .replace(/{{QUIZ_DURATION_SECONDS}}/g, (quizDuration * 60).toString());

    // Injeção de restrições negativas baseadas nas opções selecionadas
    let finalInstructions = "";
    if (!selectedOptions.includes("quizzes") || numberOfQuestions === 0) {
      finalInstructions += "\n- 🚫 **SEM QUIZ:** Estás PROIBIDO de gerar qualquer banco de questões (`question_banks`) ou atividade do tipo `quiz`. O curso deve ser apenas informativo.";
    }

    setDynamicPrompt(finalPrompt + finalInstructions)
  }, [depth, difficulty, numberOfQuestions, quizDuration, selectedOptions, masterPrompt])

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
        status: "ready", // Já marcamos como ready pois o processamento será feito no servidor
        progress: 100
      }
      
      setFiles(prev => [...prev, newFile])
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
          status: "ready",
          progress: 100
        }
        
        setFiles(prev => [...prev, newFile])
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

  const [jsonError, setJsonError] = useState<string | null>(null)

  const validateAndSetCourse = (raw: string) => {
    try {
      setJsonError(null);
      let content = raw.trim();
      if (!content) {
        setGeneratedCourse(null);
        return;
      }

      if (content.includes("```")) {
        const matches = content.match(/```(?:json)?\s*([\s\S]*?)```/);
        if (matches && matches[1]) {
          content = matches[1].trim();
        }
      }

      const json = JSON.parse(content);
      
      const normalizedCourse: Partial<MoodleCourse> = {
        course_name: json.course_name || json.name || (json.course && json.course.name) || "Curso Sem Nome",
        course_shortname: json.course_shortname || json.shortname || "IA_CURSO",
        source_file: json.source_file || json.filename || "manual.pdf",
        course_summary: json.course_summary || json.summary || json.description || "",
        question_banks: json.question_banks || (json.course && json.course.question_banks) || [],
        activities: json.activities || (json.course && json.course.activities) || [],
        // Injetar a pasta de imagens se houver um PDF na aba Fábrica
        image_folder: factoryPdfFile ? factoryPdfFile.name.split('.').slice(0, -1).join('.') : (json.image_folder || "")
      };

      if (normalizedCourse.activities!.length === 0) {
        setJsonError("Erro: O JSON não contém atividades (atividades: []).");
        setGeneratedCourse(null);
        return;
      }

      setGeneratedCourse(normalizedCourse as MoodleCourse);
      toast({
        title: "JSON Válido!",
        description: "Curso processado com sucesso.",
      });
      
    } catch (err) {
      setJsonError("Erro de sintaxe JSON: Verifique aspas, vírgulas ou se o texto está truncado.");
      setGeneratedCourse(null);
    }
  };

  const handleJsonUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (!file) return
    const reader = new FileReader()
    reader.onload = (event) => {
      validateAndSetCourse(event.target?.result as string);
    }
    reader.readAsText(file)
  }

  const handleOpenJsonPreview = async () => {
    if (!generatedCourse || !courseName) return;

    let finalImageFolder = generatedCourse.image_folder || "";

    if (factoryPdfFile) {
      setGenerationState({ status: "extracting", progress: 50, message: "A preparar imagens para o preview..." });
      try {
        const base64Content = await fileToBase64(factoryPdfFile);
        const imgRes = await fetch("/api/send-to-moodle", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ 
            course: { course_name: courseName, course_shortname: "PREVIEW_EXTRACT" }, 
            pdfFile: { name: factoryPdfFile.name, content: base64Content },
            onlyExtract: true
          })
        });
        const imgData = await imgRes.json();
        if (imgData.success) {
          finalImageFolder = imgData.image_folder;
        }
      } catch (err) {
        console.warn("Extração pré-preview falhou:", err);
      }
    }

    const courseWithImages = {
      ...generatedCourse,
      image_folder: finalImageFolder
    };

    setGeneratedCourse(courseWithImages);
    setGenerationState({ status: "idle", progress: 0, message: "" });
    setShowPreview(true);
  };

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

    setGenerationState({ status: "extracting", progress: 5, message: "A extrair imagens e texto do PDF..." })

    try {
      // 1. Extrair Imagens no Moodle primeiro para garantir que o Preview as mostra
      let imageFolder = "";
      try {
        const file = files[0].file;
        const base64Content = await fileToBase64(file);
        
        const imgResponse = await fetch("/api/send-to-moodle", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ 
            course: { course_name: courseName, course_shortname: "TEMP_EXTRACT" }, 
            pdfFile: { name: file.name, content: base64Content },
            onlyExtract: true // Nova flag para indicar que só queremos a extração
          })
        });
        const imgData = await imgResponse.json();
        imageFolder = imgData.image_folder;
      } catch (imgErr) {
        console.warn("Falha na extração de imagens prévia:", imgErr);
      }

      // 2. Gerar o curso com a IA
      setGenerationState({ status: "generating", progress: 30, message: "A IA está a desenhar o curso..." })
      
      const formData = new FormData()
      files.forEach(f => formData.append("files", f.file))
      formData.append("config", JSON.stringify(config))
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
      
      // Criar o objeto do curso final com a pasta de imagens injetada
      const finalCourse = {
        ...data.course,
        image_folder: imageFolder || data.course?.image_folder || ""
      };

      setGeneratedCourse(finalCourse)
      setGenerationState({ status: "complete", progress: 100, message: "Curso gerado com sucesso!", course: finalCourse })
      setShowPreview(true)
    } catch (error) {
      setGenerationState({ status: "error", progress: 0, message: "Erro ao gerar curso", error: error instanceof Error ? error.message : "Unknown error" })
    }
  }

  const handleSendToMoodle = async () => {
    if (!generatedCourse || !courseName) return
    setGenerationState({ status: "sending", progress: 90, message: "A criar estrutura do curso no Moodle..." })
    try {
      const courseWithFinalName = {
        ...generatedCourse,
        course_name: courseName
      };

      const response = await fetch("/api/send-to-moodle", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ 
          course: courseWithFinalName, 
          pdfFile: null, 
          onlyExtract: false 
        })
      })

      const data = await response.json()
      
      if (!response.ok) throw new Error(data.error || "Failed to send to Moodle")

      if (data.courseId) {
        setCreatedCourseId(data.courseId)
      }
      setGenerationState({ 
        status: "complete", 
        progress: 100, 
        message: `Sucesso! [ID: ${data.courseId}] | [Código: ${courseWithFinalName.course_shortname}]` 
      })
      
      toast({
        title: "Curso Criado!",
        description: `Nome Final: ${courseWithFinalName.course_name}`,
      });
    } catch (error) {
      setGenerationState({ status: "error", progress: 0, message: "Erro ao enviar", error: error instanceof Error ? error.message : "Unknown error" })
    }
  }

  const isGenerating = generationState.status === "extracting" || 
                       generationState.status === "generating" || 
                       generationState.status === "sending"

  async function fileToBase64(file: File): Promise<string> {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onload = () => resolve((reader.result as string).split(',')[1]);
      reader.onerror = reject;
      reader.readAsDataURL(file);
    });
  }

  if (showPreview && generatedCourse) {
    return (
      <CoursePreview
        course={generatedCourse}
        onBack={() => {
          setShowPreview(false);
          setCreatedCourseId(null); // Resetar ao voltar
        }}
        onSendToMoodle={handleSendToMoodle}
        isSending={generationState.status === "sending"}
        moodleConnected={moodleStatus === "connected"}
        createdCourseId={createdCourseId}
        error={generationState.status === "error" ? generationState.error || generationState.message : null}
      />    )
  }

  return (
    <div className="space-y-6 max-w-4xl">
      <div>
        <h1 className="text-2xl font-bold text-foreground">Gerar Novo Curso</h1>
        <p className="text-muted-foreground">Escolha entre gerar um curso via IA ou carregar um JSON pré-existente</p>
      </div>

      <Tabs defaultValue="ai" className="w-full">
        <TabsList className="grid w-full grid-cols-2">
          <TabsTrigger value="ai" className="gap-2"><Sparkles className="h-4 w-4" />Gerar com IA (PDF)</TabsTrigger>
          <TabsTrigger value="json" className="gap-2"><Factory className="h-4 w-4" />Gerar com JSON</TabsTrigger>
        </TabsList>

        <TabsContent value="ai" className="space-y-6 mt-6">
          <Card className={cn("border-2", moodleStatus === "connected" ? "border-green-500 bg-green-50" : moodleStatus === "error" ? "border-red-500 bg-red-50" : "border-muted")}>
            <CardContent className="p-4 flex items-center justify-between">
              <div className="flex items-center gap-3">
                {moodleStatus === "checking" ? <Loader2 className="h-5 w-5 animate-spin text-muted-foreground" /> : moodleStatus === "connected" ? <CheckCircle2 className="h-5 w-5 text-green-600" /> : <AlertCircle className="h-5 w-5 text-red-600" />}
                <div>
                  <p className="font-medium text-foreground">{moodleStatus === "connected" ? `Conectado ao Moodle: ${moodleInfo?.siteName}` : "Moodle desconectado"}</p>
                </div>
              </div>
              <Button variant="outline" size="sm" onClick={checkMoodleConnection}>Testar Conexao</Button>
            </CardContent>
          </Card>

          <Card>
            <CardHeader><CardTitle className="flex items-center gap-2 text-lg font-semibold"><Upload className="h-5 w-5 text-primary" />Upload de Manuais PDF</CardTitle></CardHeader>
            <CardContent>
              <div onDrop={handleDrop} onDragOver={(e) => { e.preventDefault(); setIsDragOver(true) }} onDragLeave={() => setIsDragOver(false)} className={cn("border-2 border-dashed rounded-lg p-8 text-center transition-colors", isDragOver ? "border-primary bg-primary/5" : "border-border hover:border-primary/50")}>
                <div className="flex flex-col items-center gap-4">
                  <div className="h-16 w-16 rounded-full bg-primary/10 flex items-center justify-center"><Upload className="h-8 w-8 text-primary" /></div>
                  <p className="text-lg font-medium">Arraste os ficheiros PDF aqui</p>
                  <input type="file" accept=".pdf" multiple onChange={handleFileInput} className="hidden" id="file-upload" />
                  <Button asChild variant="outline"><label htmlFor="file-upload" className="cursor-pointer">Selecionar Ficheiros</label></Button>
                </div>
              </div>
              {files.length > 0 && (
                <div className="mt-6 space-y-3">
                  {files.map((file) => (
                    <div key={file.id} className="flex items-center gap-4 p-3 rounded-lg bg-muted">
                      <FileText className="h-8 w-8 text-primary" /><div className="flex-1 truncate"><p className="font-medium">{file.name}</p></div>
                      <Button variant="ghost" size="icon" onClick={() => removeFile(file.id)}><X className="h-4 w-4" /></Button>
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>

          <Card>
            <CardHeader><CardTitle className="flex items-center gap-2 text-lg font-semibold"><Sparkles className="h-5 w-5 text-primary" />Configuracoes do Curso</CardTitle></CardHeader>
            <CardContent className="space-y-6">
              <div className="space-y-2">
                <Label htmlFor="course-name">Nome do Curso</Label>
                <Input id="course-name" placeholder="Ex: Seguranca no Trabalho" value={courseName} onChange={(e) => setCourseName(e.target.value)} />
              </div>
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div className="space-y-2">
                  <Label>Profundidade</Label>
                  <Select value={depth} onValueChange={(v) => setDepth(v as any)}>
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="Resumo Executivo">Resumo Executivo</SelectItem>
                      <SelectItem value="Profissional">Profissional</SelectItem>
                      <SelectItem value="Especialista Técnico">Especialista Técnico</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label>Dificuldade</Label>
                  <Select value={difficulty} onValueChange={(v) => setDifficulty(v as any)}>
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent><SelectItem value="easy">Facil</SelectItem><SelectItem value="medium">Media</SelectItem><SelectItem value="hard">Dificil</SelectItem></SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label>Questões</Label>
                  <Input type="number" value={numberOfQuestions} onChange={(e) => setNumberOfQuestions(Number(e.target.value))} />
                </div>
                <div className="space-y-2">
                  <Label>Duração (min)</Label>
                  <Input type="number" value={quizDuration} onChange={(e) => setQuizDuration(Number(e.target.value))} />
                </div>
              </div>

              <div className="space-y-4 pt-4 border-t">
                <div className="flex items-center justify-between">
                  <Label className="text-base font-semibold">Recursos Adicionais</Label>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  {generationOptions.map((option) => {
                    const Icon = option.icon
                    const isSelected = selectedOptions.includes(option.id)
                    const isComingSoon = option.id === "videos" || option.id === "certificate"
                    
                    return (
                      <Card 
                        key={option.id}
                        className={cn(
                          "transition-all",
                          isComingSoon 
                            ? "border-muted bg-muted/5 cursor-not-allowed opacity-60" 
                            : "cursor-pointer hover:border-primary/50",
                          !isComingSoon && isSelected ? "border-primary bg-primary/5 shadow-sm" : "border-muted"
                        )}
                        onClick={() => !isComingSoon && toggleOption(option.id)}
                      >
                        <CardContent className="p-4 flex items-start gap-4">
                          <div className={cn(
                            "h-10 w-10 rounded-lg flex items-center justify-center shrink-0",
                            !isComingSoon && isSelected ? "bg-primary text-primary-foreground" : "bg-muted text-muted-foreground"
                          )}>
                            <Icon className="h-5 w-5" />
                          </div>
                          <div className="flex-1">
                            <div className="flex items-center justify-between gap-2">
                              <p className="font-semibold text-sm text-foreground">{option.label}</p>
                              {isComingSoon && <Badge variant="outline" className="text-[8px] h-4">Brevemente</Badge>}
                              {!isComingSoon && isSelected && <CheckCircle2 className="h-4 w-4 text-primary" />}
                            </div>
                            <p className="text-[10px] text-muted-foreground mt-1 leading-tight">{option.description}</p>
                          </div>
                        </CardContent>
                      </Card>
                    )
                  })}
                </div>
              </div>
            </CardContent>
          </Card>
          
          <div className="flex gap-3">
            <Button 
              variant="outline" 
              size="lg" 
              className="flex-1 gap-2"
              onClick={handleCopyPrompt}
              disabled={files.length === 0 || !courseName || isCopyingPrompt}
            >
              {isCopyingPrompt ? <Loader2 className="h-5 w-5 animate-spin" /> : <ClipboardCopy className="h-5 w-5" />}
              Copiar Prompt
            </Button>
            <Button 
              size="lg" 
              className="flex-[2] gap-2" 
              onClick={handleGenerate} 
              disabled={files.length === 0 || !courseName || isGenerating}
            >
              {isGenerating ? <Loader2 className="h-5 w-5 animate-spin" /> : <Sparkles className="h-5 w-5" />}
              Gerar Curso com IA
            </Button>
          </div>

          {/* Status da Geração / Sucesso */}
          {(isGenerating || generationState.status === "error" || generationState.status === "complete") && (
            <Card className={cn(
              "mt-6 border-2",
              generationState.status === "error" && "border-red-500 bg-red-50",
              generationState.status === "complete" && "border-green-500 bg-green-50"
            )}>
              <CardContent className="p-6">
                <div className="flex items-center gap-4">
                  {isGenerating && <Loader2 className="h-6 w-6 text-primary animate-spin" />}
                  {generationState.status === "error" && <AlertCircle className="h-6 w-6 text-red-500" />}
                  {generationState.status === "complete" && <CheckCircle2 className="h-6 w-6 text-green-500" />}
                  <div className="flex-1">
                    <p className="font-bold text-foreground text-lg">{generationState.message}</p>
                    {generationState.status === "complete" && generatedCourse && (
                      <div className="mt-3 text-sm space-y-2 bg-white/80 p-3 rounded-lg border shadow-sm">
                        <p className="flex items-center gap-2">
                          <span className="px-2 py-0.5 bg-primary/10 text-primary font-bold rounded text-xs uppercase">Nome no Moodle</span>
                          <span className="font-semibold text-foreground">{courseName}</span>
                        </p>
                        <p className="flex items-center gap-2">
                          <span className="px-2 py-0.5 bg-muted text-muted-foreground font-bold rounded text-xs uppercase">Sugestão da IA</span>
                          <span className="text-muted-foreground italic">{generatedCourse.course_name}</span>
                        </p>
                        
                        {createdCourseId && (
                          <div className="pt-2 border-t mt-2 space-y-2">
                            <Button 
                              asChild 
                              className="w-full bg-green-600 hover:bg-green-700 text-white gap-2"
                              size="sm"
                            >
                              <a 
                                href={`${process.env.NEXT_PUBLIC_MOODLE_URL || "http://localhost:8080"}/course/view.php?id=${createdCourseId}`} 
                                target="_blank" 
                                rel="noopener noreferrer"
                              >
                                <ExternalLink className="h-4 w-4" />
                                Aceder ao Curso no Moodle
                              </a>
                            </Button>
                            <Button 
                              variant="ghost" 
                              size="sm" 
                              className="w-full text-xs text-muted-foreground hover:text-primary"
                              onClick={() => {
                                setFiles([]); setCourseName(""); setGeneratedCourse(null); 
                                setCreatedCourseId(null); setGenerationState({ status: "idle", progress: 0, message: "" })
                              }}
                            >
                              Criar Novo Curso
                            </Button>
                          </div>
                        )}
                      </div>
                    )}
                    {generationState.error && (
                      <p className="text-sm text-red-600 mt-1 font-medium">{generationState.error}</p>
                    )}
                  </div>
                </div>
                {isGenerating && (
                  <div className="mt-4 space-y-2">
                    <Progress value={generationState.progress} className="h-2" />
                    <p className="text-xs text-muted-foreground text-right font-mono">{generationState.progress}%</p>
                  </div>
                )}
              </CardContent>
            </Card>
          )}
        </TabsContent>

        <TabsContent value="json" className="mt-6 space-y-6">
          <Card>
            <CardHeader><CardTitle className="flex items-center gap-2"><Factory className="h-5 w-5 text-primary" />Configurador de Prompt Master</CardTitle></CardHeader>
            <CardContent className="space-y-8">
              <div className="space-y-2">
                <Label htmlFor="json-course-name">Nome do Curso</Label>
                <Input id="json-course-name" placeholder="Ex: Seguranca no Trabalho" value={courseName} onChange={(e) => setCourseName(e.target.value)} />
              </div>

              {/* LAYOUT FIX: Mais espaço e grid melhorada para não sobrepor */}
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 py-4 border-y border-border">
                <div className="space-y-3">
                  <Label className="text-primary font-bold">Profundidade</Label>
                  <Select value={depth} onValueChange={(v) => setDepth(v as any)}>
                    <SelectTrigger className="w-full bg-white"><SelectValue /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="Resumo Executivo">Resumo Executivo</SelectItem>
                      <SelectItem value="Profissional">Profissional</SelectItem>
                      <SelectItem value="Especialista Técnico">Especialista Técnico</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-3">
                  <Label className="text-primary font-bold">Dificuldade</Label>
                  <Select value={difficulty} onValueChange={(v) => setDifficulty(v as any)}>
                    <SelectTrigger className="w-full bg-white"><SelectValue /></SelectTrigger>
                    <SelectContent><SelectItem value="easy">Facil</SelectItem><SelectItem value="medium">Media</SelectItem><SelectItem value="hard">Dificil</SelectItem></SelectContent>
                  </Select>
                </div>
                <div className="space-y-3">
                  <Label className="text-primary font-bold">Questões</Label>
                  <Input type="number" value={numberOfQuestions} onChange={(e) => setNumberOfQuestions(Number(e.target.value))} className="bg-white" />
                </div>
                <div className="space-y-3">
                  <Label className="text-primary font-bold">Duração (min)</Label>
                  <Input type="number" value={quizDuration} onChange={(e) => setQuizDuration(Number(e.target.value))} className="bg-white" />
                </div>
              </div>

              {/* RECURSOS ADICIONAIS - SINCRONIZADOS */}
              <div className="space-y-4 py-4">
                <div className="flex items-center justify-between">
                  <Label className="text-base font-semibold">Recursos Adicionais</Label>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  {generationOptions.map((option) => {
                    const Icon = option.icon
                    const isSelected = selectedOptions.includes(option.id)
                    const isComingSoon = option.id === "videos" || option.id === "certificate"
                    
                    return (
                      <Card 
                        key={option.id}
                        className={cn(
                          "transition-all",
                          isComingSoon 
                            ? "border-muted bg-muted/5 cursor-not-allowed opacity-60" 
                            : "cursor-pointer hover:border-primary/50",
                          !isComingSoon && isSelected ? "border-primary bg-primary/5 shadow-sm" : "border-muted"
                        )}
                        onClick={() => !isComingSoon && toggleOption(option.id)}
                      >
                        <CardContent className="p-4 flex items-start gap-4">
                          <div className={cn(
                            "h-10 w-10 rounded-lg flex items-center justify-center shrink-0",
                            !isComingSoon && isSelected ? "bg-primary text-primary-foreground" : "bg-muted text-muted-foreground"
                          )}>
                            <Icon className="h-5 w-5" />
                          </div>
                          <div className="flex-1">
                            <div className="flex items-center justify-between gap-2">
                              <p className="font-semibold text-sm text-foreground">{option.label}</p>
                              {isComingSoon && <Badge variant="outline" className="text-[8px] h-4">Brevemente</Badge>}
                              {!isComingSoon && isSelected && <CheckCircle2 className="h-4 w-4 text-primary" />}
                            </div>
                            <p className="text-[10px] text-muted-foreground mt-1 leading-tight">{option.description}</p>
                          </div>
                        </CardContent>
                      </Card>
                    )
                  })}
                </div>
              </div>

              <div className="space-y-4 bg-muted/30 p-4 rounded-lg border">
                <div className="flex items-center justify-between flex-wrap gap-2">
                  <h4 className="font-bold text-primary flex items-center gap-2"><ClipboardCopy className="h-4 w-4" />Passo 1: Copie o Prompt</h4>
                  <Button 
                    variant={copiedPrompt ? "outline" : "default"} 
                    size="sm" 
                    onClick={async () => { 
                      await navigator.clipboard.writeText(dynamicPrompt); 
                      setCopiedPrompt(true);
                      toast({ title: "Copiado!", description: "Prompt na área de transferência." }); 
                      setTimeout(() => setCopiedPrompt(false), 2000);
                    }} 
                    className={cn("gap-2 transition-all", copiedPrompt && "border-green-500 text-green-600")}
                  >
                    {copiedPrompt ? <CheckCircle2 className="h-4 w-4" /> : <Copy className="h-4 w-4" />}
                    {copiedPrompt ? "Copiado!" : "Copiar Prompt"}
                  </Button>
                </div>
                <Textarea value={dynamicPrompt} readOnly className="font-mono text-[10px] h-[250px] bg-white" />
              </div>

              <div className="space-y-4 bg-amber-50/50 p-4 rounded-lg border border-amber-200">
                <h4 className="font-bold text-amber-700 flex items-center gap-2"><FileText className="h-4 w-4" />Passo 2: PDF para Imagens</h4>
                <Input type="file" accept=".pdf" onChange={(e) => setFactoryPdfFile(e.target.files?.[0] || null)} className="bg-white" />
              </div>

              <div className="space-y-4 pt-6 border-t border-border bg-primary/5 p-4 rounded-lg border border-primary/20">
                <h4 className="font-bold text-primary flex items-center gap-2"><Upload className="h-4 w-4" />Passo 3: Upload do JSON Gerado</h4>
                <Input type="file" accept=".json" onChange={handleJsonUpload} className="bg-white mb-2" />
                {jsonError && <p className="text-xs text-red-500 font-medium">{jsonError}</p>}
                {generatedCourse && (
                  <p className="text-xs text-primary font-bold">
                    ✓ JSON Válido ({(generatedCourse.activities || []).length} atividades)
                  </p>
                )}
                <p className="text-xs text-muted-foreground mt-2">
                  Após usar o prompt acima na IA, carregue o ficheiro .json gerado para criar o curso no Moodle.
                </p>
              </div>

              <Button 
                size="lg" 
                className={cn(
                  "w-full py-10 text-xl font-black transition-all uppercase tracking-widest",
                  (generatedCourse && courseName) ? "bg-primary text-primary-foreground hover:scale-[1.01] shadow-xl" : "bg-muted text-muted-foreground"
                )} 
                disabled={!generatedCourse || !courseName} 
                onClick={handleOpenJsonPreview}
              >
                {!courseName ? "Defina o Nome do Curso" : generatedCourse ? "🚀 Visualizar e Criar Curso" : "Aguardando JSON Válido..."}
              </Button>

              {/* Status de Sucesso (após envio) na aba JSON */}
              {generationState.status === "complete" && generatedCourse && (
                <Card className="mt-6 border-2 border-green-500 bg-green-50">
                  <CardContent className="p-6">
                    <div className="flex items-center gap-4">
                      <CheckCircle2 className="h-6 w-6 text-green-500" />
                      <div className="flex-1">
                        <p className="font-bold text-foreground text-lg">{generationState.message}</p>
                        <div className="mt-3 text-sm space-y-2 bg-white/80 p-3 rounded-lg border shadow-sm">
                          <p className="flex items-center gap-2">
                            <span className="px-2 py-0.5 bg-primary/10 text-primary font-bold rounded text-xs uppercase">Nome no Moodle</span>
                            <span className="font-semibold text-foreground">{courseName}</span>
                          </p>
                          <p className="flex items-center gap-2">
                            <span className="px-2 py-0.5 bg-muted text-muted-foreground font-bold rounded text-xs uppercase">Sugestão da IA</span>
                            <span className="text-muted-foreground italic">{generatedCourse.course_name}</span>
                          </p>

                          {createdCourseId && (
                            <div className="pt-2 border-t mt-2 space-y-2">
                              <Button 
                                asChild 
                                className="w-full bg-green-600 hover:bg-green-700 text-white gap-2"
                                size="sm"
                              >
                                <a 
                                  href={`http://localhost:8080/course/view.php?id=${createdCourseId}`} 
                                  target="_blank" 
                                  rel="noopener noreferrer"
                                >
                                  <ExternalLink className="h-4 w-4" />
                                  Aceder ao Curso no Moodle
                                </a>
                              </Button>
                              <Button 
                                variant="ghost" 
                                size="sm" 
                                className="w-full text-xs text-muted-foreground hover:text-primary"
                                onClick={() => {
                                  setFiles([]); setCourseName(""); setGeneratedCourse(null); 
                                  setCreatedCourseId(null); setGenerationState({ status: "idle", progress: 0, message: "" })
                                }}
                              >
                                Criar Novo Curso
                              </Button>
                            </div>
                          )}
                        </div>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              )}
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  )
}
