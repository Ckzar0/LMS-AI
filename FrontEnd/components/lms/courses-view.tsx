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
  rating: number
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
            placeholder="Pesquisar cursos..." 
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
              className="overflow-hidden hover:shadow-lg transition-shadow cursor-pointer group flex flex-col"
              onClick={() => onCourseSelect(course.id.toString())}
            >
              <div className="relative h-40 bg-muted flex items-center justify-center overflow-hidden">
                <img 
                  src={`https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&h=200&fit=crop&q=80`} 
                  alt={course.name}
                  className="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 opacity-80"
                />
                <div className="absolute inset-0 bg-primary/20 group-hover:bg-primary/30 transition-colors" />
                <Badge 
                  className={`absolute top-3 right-3 z-10 bg-green-500 hover:bg-green-600`}
                >
                  Publicado
                </Badge>
              </div>
              <CardContent className="p-4 flex-1 flex flex-col">
                <div className="flex items-center justify-between gap-2 mb-2">
                   <Badge variant="secondary" className="text-[10px] font-mono px-1.5 py-0">{course.shortname}</Badge>
                   <div className="flex items-center gap-1">
                      <StarRating rating={course.rating || 0} size="sm" readonly />
                      <span className="text-[10px] font-bold text-muted-foreground">{(course.rating || 0).toFixed(1)}</span>
                   </div>
                </div>
                <h3 className="font-semibold text-foreground line-clamp-2 mb-2 h-10 leading-tight">{course.name}</h3>
                
                <div className="flex items-center gap-4 text-xs text-muted-foreground mb-4 mt-auto pt-2">
                  <span className="flex items-center gap-1">
                    <Clock className="h-3 w-3" />
                    Completo
                  </span>
                  <span className="flex items-center gap-1">
                    <BookOpen className="h-3 w-3" />
                    ID: {course.id}
                  </span>
                </div>

                <div className="flex items-center justify-between pt-3 border-t border-border mt-auto">
                  <div className="flex items-center gap-1 text-xs text-muted-foreground">
                    <Users className="h-3 w-3" />
                    <span>0 inscritos</span>
                  </div>
                  <div className="flex items-center gap-1 text-xs text-muted-foreground">
                    <FileText className="h-3 w-3" />
                    <span className="truncate max-w-[80px]">manual.pdf</span>
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
