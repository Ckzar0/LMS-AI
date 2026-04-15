"use client"

import { useState } from "react"
import { Search, Filter, Play, Users, Clock, FileText, MoreVertical, Star } from "lucide-react"
import { StarRating } from "./star-rating"
import { Card, CardContent } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Badge } from "@/components/ui/badge"
import { cn } from "@/lib/utils"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"

interface CoursesViewProps {
  onCourseSelect: (courseId: string) => void
}

const courses = [
  {
    id: "1",
    name: "Segurança no Trabalho - Normas ISO 45001",
    description: "Formação completa sobre normas de segurança ocupacional e gestão de riscos.",
    thumbnail: "/api/placeholder/400/200",
    modules: 8,
    videos: 24,
    duration: "4h 30min",
    enrolled: 45,
    completions: 38,
    status: "published",
    sourceFile: "manual_iso_45001.pdf",
    createdAt: "2024-01-15",
    rating: 4.7,
    totalReviews: 38,
    qualityScore: 92
  },
  {
    id: "2",
    name: "Operação de Empilhadores",
    description: "Curso certificado para operação segura de empilhadores industriais.",
    thumbnail: "/api/placeholder/400/200",
    modules: 6,
    videos: 18,
    duration: "3h 15min",
    enrolled: 32,
    completions: 28,
    status: "published",
    sourceFile: "manual_empilhadores_v2.pdf",
    createdAt: "2024-01-20",
    rating: 4.2,
    totalReviews: 28,
    qualityScore: 85
  },
  {
    id: "3",
    name: "Procedimentos de Qualidade - QMS",
    description: "Sistema de gestão da qualidade e procedimentos operacionais.",
    thumbnail: "/api/placeholder/400/200",
    modules: 5,
    videos: 15,
    duration: "2h 45min",
    enrolled: 0,
    completions: 0,
    status: "generating",
    sourceFile: "qms_procedures_2024.pdf",
    createdAt: "2024-02-01",
    rating: 0,
    totalReviews: 0,
    qualityScore: null
  },
  {
    id: "4",
    name: "Manutenção Preventiva de Equipamentos",
    description: "Técnicas e procedimentos de manutenção preventiva industrial.",
    thumbnail: "/api/placeholder/400/200",
    modules: 4,
    videos: 12,
    duration: "2h 00min",
    enrolled: 0,
    completions: 0,
    status: "generating",
    sourceFile: "maintenance_guide.pdf",
    createdAt: "2024-02-05",
    rating: 0,
    totalReviews: 0,
    qualityScore: null
  },
  {
    id: "5",
    name: "HACCP - Segurança Alimentar",
    description: "Princípios e implementação do sistema HACCP na indústria alimentar.",
    thumbnail: "/api/placeholder/400/200",
    modules: 7,
    videos: 21,
    duration: "3h 45min",
    enrolled: 67,
    completions: 52,
    status: "published",
    sourceFile: "haccp_manual_2024.pdf",
    createdAt: "2024-01-10",
    rating: 4.8,
    totalReviews: 52,
    qualityScore: 95
  },
  {
    id: "6",
    name: "Primeiros Socorros no Local de Trabalho",
    description: "Formação em primeiros socorros e resposta a emergências.",
    thumbnail: "/api/placeholder/400/200",
    modules: 5,
    videos: 16,
    duration: "2h 30min",
    enrolled: 89,
    completions: 76,
    status: "published",
    sourceFile: "first_aid_procedures.pdf",
    createdAt: "2024-01-05",
    rating: 3.9,
    totalReviews: 76,
    qualityScore: 78
  },
]

export function CoursesView({ onCourseSelect }: CoursesViewProps) {
  const [searchTerm, setSearchTerm] = useState("")
  const [filter, setFilter] = useState<"all" | "published" | "generating">("all")

  const filteredCourses = courses.filter(course => {
    const matchesSearch = course.name.toLowerCase().includes(searchTerm.toLowerCase())
    const matchesFilter = filter === "all" || course.status === filter
    return matchesSearch && matchesFilter
  })

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-foreground">Cursos</h1>
          <p className="text-muted-foreground">Gerencie todos os cursos gerados automaticamente</p>
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
            Todos
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
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {filteredCourses.map((course) => (
          <Card 
            key={course.id} 
            className="overflow-hidden hover:shadow-lg transition-shadow cursor-pointer group"
            onClick={() => onCourseSelect(course.id)}
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
              <h3 className="font-semibold text-foreground line-clamp-2 mb-2">{course.name}</h3>
              <p className="text-sm text-muted-foreground line-clamp-2 mb-4">{course.description}</p>
              
              <div className="flex items-center gap-4 text-sm text-muted-foreground mb-3">
                <span className="flex items-center gap-1">
                  <Play className="h-4 w-4" />
                  {course.videos} vídeos
                </span>
                <span className="flex items-center gap-1">
                  <Clock className="h-4 w-4" />
                  {course.duration}
                </span>
              </div>

              {course.status === "published" && course.rating > 0 && (
                <div className="flex items-center justify-between mb-3">
                  <StarRating rating={course.rating} totalReviews={course.totalReviews} size="sm" />
                  {course.qualityScore && (
                    <span className={cn(
                      "text-xs font-medium px-2 py-0.5 rounded-full",
                      course.qualityScore >= 90 ? "bg-green-100 text-green-700" :
                      course.qualityScore >= 80 ? "bg-blue-100 text-blue-700" :
                      course.qualityScore >= 70 ? "bg-amber-100 text-amber-700" :
                      "bg-red-100 text-red-700"
                    )}>
                      Qualidade: {course.qualityScore}%
                    </span>
                  )}
                </div>
              )}

              <div className="flex items-center justify-between pt-3 border-t border-border">
                <div className="flex items-center gap-1 text-sm text-muted-foreground">
                  <Users className="h-4 w-4" />
                  <span>{course.enrolled} inscritos</span>
                </div>
                <div className="flex items-center gap-1 text-xs text-muted-foreground">
                  <FileText className="h-3 w-3" />
                  <span className="truncate max-w-[100px]">{course.sourceFile}</span>
                </div>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  )
}
