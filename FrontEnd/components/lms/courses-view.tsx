"use client"

import { useState, useEffect } from "react"
import { Search, Play, Users, Clock, FileText, Loader2, BookOpen } from "lucide-react"
import { StarRating } from "./star-rating"
import { Card, CardContent } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Badge } from "@/components/ui/badge"
import { cn } from "@/lib/utils"

interface Course {
  id: number
  name: string
  shortname: string
  description: string
  status: string
  enrolled: number
  progress: number
  timecreated: number
  sourceFile: string
}

interface CoursesViewProps {
  onCourseSelect: (courseId: string) => void
}

export function CoursesView({ onCourseSelect }: CoursesViewProps) {
  const [searchTerm, setSearchTerm] = useState("")
  const [filter, setFilter] = useState<"all" | "published" | "generating">("all")
  const [courses, setCourses] = useState<Course[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    async function fetchCourses() {
      setLoading(true)
      try {
        const response = await fetch('/api/dashboard-stats')
        if (!response.ok) throw new Error("Falha ao carregar cursos")
        const data = await response.json()
        
        // A API dashboard-stats retorna 'recentCourses', vamos usar isso como base
        // Nota: Em uma fase posterior, podemos criar uma API /api/courses para paginação
        setCourses(data.recentCourses || [])
      } catch (err) {
        setError(err instanceof Error ? err.message : "Erro ao carregar dados")
      } finally {
        setLoading(false)
      }
    }
    fetchCourses()
  }, [])

  const filteredCourses = courses.filter(course => {
    const matchesSearch = course.name.toLowerCase().includes(searchTerm.toLowerCase()) || 
                         course.shortname.toLowerCase().includes(searchTerm.toLowerCase())
    const matchesFilter = filter === "all" || course.status === filter
    return matchesSearch && matchesFilter
  })

  if (loading) {
    return (
      <div className="flex flex-col items-center justify-center h-64 gap-4">
        <Loader2 className="h-8 w-8 animate-spin text-primary" />
        <p className="text-muted-foreground">A carregar catálogo de cursos...</p>
      </div>
    )
  }

  if (error) {
    return (
      <div className="p-8 text-center border-2 border-dashed border-destructive/20 rounded-xl bg-destructive/5">
        <p className="text-destructive font-medium">Erro ao carregar cursos: {error}</p>
        <Button onClick={() => window.location.reload()} variant="outline" className="mt-4">
          Tentar Novamente
        </Button>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-foreground">Cursos</h1>
          <p className="text-muted-foreground">Gerencie todos os cursos gerados automaticamente no Moodle</p>
        </div>
      </div>

      {/* Filters */}
      <div className="flex items-center gap-4">
        <div className="relative flex-1 max-w-md">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input 
            placeholder="Pesquisar por nome ou código..." 
            className="pl-10"
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
          />
        </div>
        <div className="flex items-center gap-2">
          <Button 
            variant={filter === "all" ? "default" : "outline"} 
            size="sm"
            onClick={() => setFilter("all")}
          >
            Todos ({courses.length})
          </Button>
          <Button 
            variant={filter === "published" ? "default" : "outline"} 
            size="sm"
            onClick={() => setFilter("published")}
          >
            Publicados
          </Button>
          <Button 
            variant={filter === "generating" ? "default" : "outline"} 
            size="sm"
            onClick={() => setFilter("generating")}
          >
            Em Geração
          </Button>
        </div>
      </div>

      {/* Courses Grid */}
      {filteredCourses.length === 0 ? (
        <div className="p-12 text-center border-2 border-dashed border-border rounded-xl">
          <BookOpen className="h-12 w-12 text-muted-foreground mx-auto mb-4 opacity-20" />
          <h3 className="text-lg font-medium">Nenhum curso encontrado</h3>
          <p className="text-muted-foreground mt-2">Tente ajustar os seus filtros ou termos de pesquisa.</p>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {filteredCourses.map((course) => (
            <Card 
              key={course.id} 
              className="overflow-hidden hover:shadow-lg transition-shadow cursor-pointer group"
              onClick={() => onCourseSelect(course.id.toString())}
            >
              <div className="relative h-40 bg-gradient-to-br from-primary/20 to-primary/5 flex items-center justify-center">
                <div className="absolute inset-0 bg-primary/10 group-hover:bg-primary/20 transition-colors" />
                <Play className="h-12 w-12 text-primary opacity-50 group-hover:opacity-100 transition-opacity" />
                <Badge 
                  className={`absolute top-3 right-3 ${
                    course.status === "published" 
                      ? "bg-green-500 hover:bg-green-600" 
                      : "bg-amber-500 hover:bg-amber-600"
                  }`}
                >
                  {course.status === "published" ? "Publicado" : "A gerar..."}
                </Badge>
              </div>
              <CardContent className="p-4">
                <div className="flex items-center gap-2 mb-2">
                   <Badge variant="outline" className="text-[10px] font-mono">{course.shortname}</Badge>
                </div>
                <h3 className="font-semibold text-foreground line-clamp-2 mb-2 h-12">{course.name}</h3>
                
                <div className="flex items-center gap-4 text-sm text-muted-foreground mb-4">
                  <span className="flex items-center gap-1">
                    <Clock className="h-4 w-4" />
                    Duração N/A
                  </span>
                  <span className="flex items-center gap-1">
                    <BookOpen className="h-4 w-4" />
                    Moodle ID: {course.id}
                  </span>
                </div>

                <div className="flex items-center justify-between pt-3 border-t border-border">
                  <div className="flex items-center gap-1 text-sm text-muted-foreground">
                    <Users className="h-4 w-4" />
                    <span>{course.enrolled} inscritos</span>
                  </div>
                  <div className="flex items-center gap-1 text-xs text-muted-foreground">
                    <FileText className="h-3 w-3" />
                    <span className="truncate max-w-[100px]">{course.sourceFile || "manual.pdf"}</span>
                  </div>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}
    </div>
  )
}
