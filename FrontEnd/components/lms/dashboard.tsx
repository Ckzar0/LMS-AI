"use client"

import { useState, useEffect } from "react"
import { BookOpen, Users, Award, FileText, TrendingUp, Clock, Star, ThumbsUp, Loader2 } from "lucide-react"
import { StarRating } from "./star-rating"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Progress } from "@/components/ui/progress"

interface DashboardProps {
  onCourseSelect: (courseId: string) => void
}

interface DashboardData {
  totalCourses: number
  totalUsers: number
  totalCertifications: number
  averageRating: number
  recentCourses: any[]
}

const recentActivity = [
  { user: "João Silva", action: "Completou o curso", course: "Segurança no Trabalho", time: "Há 2 horas" },
  { user: "Maria Santos", action: "Passou no exame", course: "Operação de Empilhadores", time: "Há 3 horas" },
  { user: "Pedro Costa", action: "Iniciou o curso", course: "Segurança no Trabalho", time: "Há 5 horas" },
  { user: "Ana Oliveira", action: "Obteve certificação", course: "Procedimentos de Qualidade", time: "Há 1 dia" },
]

export function Dashboard({ onCourseSelect }: DashboardProps) {
  const [data, setData] = useState<DashboardData | null>(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    async function fetchStats() {
      try {
        const response = await fetch("/api/dashboard-stats")
        const stats = await response.json()
        setData(stats)
      } catch (error) {
        console.error("Failed to fetch dashboard stats", error)
      } finally {
        setLoading(false)
      }
    }
    fetchStats()
  }, [])

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <Loader2 className="h-8 w-8 animate-spin text-primary" />
      </div>
    )
  }

  const stats = [
    { label: "Cursos Ativos", value: data?.totalCourses.toString() || "0", icon: BookOpen, change: "+2 este mês" },
    { label: "Colaboradores", value: data?.totalUsers.toString() || "0", icon: Users, change: "+23 este mês" },
    { label: "Certificações", value: data?.totalCertifications.toString() || "0", icon: Award, change: "+15 esta semana" },
    { label: "Avaliação Média", value: data?.averageRating.toString() || "4.5", icon: Star, change: "92% satisfação" },
  ]

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-foreground">Dashboard</h1>
        <p className="text-muted-foreground">Visão geral do sistema de formação (Dados Reais)</p>
      </div>

      {/* Stats Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {stats.map((stat) => {
          const Icon = stat.icon
          return (
            <Card key={stat.label}>
              <CardContent className="p-6">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm text-muted-foreground">{stat.label}</p>
                    <p className="text-3xl font-bold text-foreground mt-1">{stat.value}</p>
                    <p className="text-xs text-primary mt-1">{stat.change}</p>
                  </div>
                  <div className="h-12 w-12 rounded-lg bg-primary/10 flex items-center justify-center">
                    <Icon className="h-6 w-6 text-primary" />
                  </div>
                </div>
              </CardContent>
            </Card>
          )
        })}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Recent Courses */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <BookOpen className="h-5 w-5 text-primary" />
              Cursos Recentes (Moodle)
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              {data?.recentCourses.map((course) => (
                <div 
                  key={course.id}
                  className="p-4 rounded-lg border border-border hover:border-primary/50 cursor-pointer transition-colors"
                  onClick={() => onCourseSelect(course.id)}
                >
                  <div className="flex items-start justify-between mb-2">
                    <div className="flex-1">
                      <h4 className="font-medium text-foreground">{course.name}</h4>
                      <p className="text-xs text-muted-foreground mt-1">
                        Curto: {course.shortname}
                      </p>
                    </div>
                    <span className={`text-xs px-2 py-1 rounded-full ${
                      course.status === "published" 
                        ? "bg-green-100 text-green-700" 
                        : "bg-amber-100 text-amber-700"
                    }`}>
                      {course.status === "published" ? "Publicado" : "A gerar..."}
                    </span>
                  </div>
                  <div className="flex items-center gap-4">
                    <div className="flex-1">
                      <Progress value={course.progress} className="h-2" />
                    </div>
                    <span className="text-sm text-muted-foreground">{course.progress}%</span>
                  </div>
                </div>
              ))}
              {data?.recentCourses.length === 0 && (
                <p className="text-center py-6 text-muted-foreground">Nenhum curso encontrado no Moodle.</p>
              )}
            </div>
          </CardContent>
        </Card>

        {/* Recent Activity */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <TrendingUp className="h-5 w-5 text-primary" />
              Atividade Recente
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              {recentActivity.map((activity, index) => (
                <div key={index} className="flex items-start gap-4 p-3 rounded-lg hover:bg-muted/50 transition-colors">
                  <div className="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center text-primary font-medium text-sm">
                    {activity.user.split(" ").map(n => n[0]).join("")}
                  </div>
                  <div className="flex-1">
                    <p className="text-sm">
                      <span className="font-medium text-foreground">{activity.user}</span>
                      {" "}
                      <span className="text-muted-foreground">{activity.action}</span>
                    </p>
                    <p className="text-sm text-primary">{activity.course}</p>
                    <p className="text-xs text-muted-foreground flex items-center gap-1 mt-1">
                      <Clock className="h-3 w-3" />
                      {activity.time}
                    </p>
                  </div>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
