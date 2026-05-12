"use client"

import { useState, useEffect } from "react"
import { BookOpen, Users, Award, TrendingUp, Clock, Star, Loader2, ArrowRight } from "lucide-react"
import { StarRating } from "./star-rating"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Progress } from "@/components/ui/progress"
import { Button } from "@/components/ui/button"

interface DashboardProps {
  onCourseSelect: (courseId: string) => void
}
interface DashboardData {
  totalCourses: number
  totalUsers: number
  totalCertifications: number
  averageRating: number
  recentActivity: any[]
  recentCourses: any[]
}

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
    { label: "Cursos Ativos", value: data?.totalCourses?.toString() || "0", icon: BookOpen, sub: "Total na plataforma" },
    { label: "Colaboradores", value: data?.totalUsers?.toString() || "0", icon: Users, sub: "Utilizadores registados" },
    { label: "Certificações", value: data?.totalCertifications?.toString() || "0", icon: Award, sub: "Emitidas via Moodle" },
    { label: "Avaliação Média", value: data?.averageRating?.toFixed(1) || "0.0", icon: Star, sub: "Satisfação dos alunos" },
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
                    <div className="flex items-baseline gap-2">
                      <p className="text-3xl font-bold text-foreground mt-1">{stat.value}</p>
                      {stat.label === "Avaliação Média" && (
                         <div className="scale-75 origin-left">
                            <StarRating rating={data?.averageRating || 0} readonly />
                         </div>
                      )}
                    </div>
                    <p className="text-xs text-muted-foreground mt-1">{stat.sub}</p>
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
        {/* Recent Courses - Expanded and Real */}
        <Card className="lg:col-span-1">
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle className="flex items-center gap-2">
              <BookOpen className="h-5 w-5 text-primary" />
              Cursos Recentes
            </CardTitle>
            <p className="text-xs text-muted-foreground">Últimos 5 cursos criados</p>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              {(data?.recentCourses || []).slice(0, 5).map((course) => (
                <div 
                  key={course.id}
                  className="p-4 rounded-lg border border-border hover:border-primary/50 cursor-pointer transition-colors group"
                  onClick={() => onCourseSelect(course.id)}
                >
                  <div className="flex items-start justify-between mb-2">
                    <div className="flex-1">
                      <h4 className="font-medium text-foreground leading-tight group-hover:text-primary transition-colors">{course.name}</h4>
                      <div className="flex items-center gap-2 mt-1">
                         <span className="text-[10px] bg-muted px-1.5 py-0.5 rounded uppercase font-bold text-muted-foreground tracking-tighter">
                            {course.shortname}
                         </span>
                         <StarRating rating={course.rating} readonly size="sm" />
                         <span className="text-xs text-muted-foreground">({course.rating.toFixed(1)})</span>
                      </div>
                    </div>
                    <div className="flex flex-col items-end gap-1">
                       <span className="text-[10px] px-2 py-0.5 rounded-full bg-green-100 text-green-700 font-bold uppercase">
                         Publicado
                       </span>
                       <span className="text-[9px] text-muted-foreground flex items-center gap-1">
                          <Clock className="h-2 w-2" />
                          {new Date(course.timecreated * 1000).toLocaleDateString()}
                       </span>
                    </div>
                  </div>
                  <div className="flex items-center gap-4 mt-3">
                    <div className="flex-1">
                      <Progress value={course.progress} className="h-1.5" />
                    </div>
                    <span className="text-xs font-bold text-foreground">{course.progress}%</span>
                  </div>
                </div>
              ))}
              {(!data?.recentCourses || data.recentCourses.length === 0) && (
                <p className="text-center py-12 text-muted-foreground">Nenhum curso encontrado no Moodle.</p>
              )}
            </div>
          </CardContent>
        </Card>

        {/* Recent Activity - REAL LOGS */}
        <Card className="lg:col-span-1">
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <TrendingUp className="h-5 w-5 text-primary" />
              Atividade da Plataforma
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              {(data?.recentActivity || []).map((activity, index) => (
                <div key={index} className="flex items-start gap-4 p-3 rounded-lg hover:bg-muted/50 transition-colors border border-transparent hover:border-border">
                  <div className="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-sm">
                    {activity.user.split(" ").map((n: string) => n[0]).join("")}
                  </div>
                  <div className="flex-1">
                    <p className="text-sm">
                      <span className="font-semibold text-foreground">{activity.user}</span>
                      {" "}
                      <span className="text-muted-foreground">{activity.action}</span>
                    </p>
                    <p className="text-sm text-primary font-medium">{activity.course}</p>
                    <p className="text-xs text-muted-foreground flex items-center gap-1 mt-1">
                      <Clock className="h-3 w-3" />
                      {new Date(activity.time * 1000).toLocaleString('pt-PT', { 
                        day: '2-digit', 
                        month: '2-digit', 
                        hour: '2-digit', 
                        minute: '2-digit' 
                      })}
                    </p>
                  </div>
                </div>
              ))}
              {(!data?.recentActivity || data.recentActivity.length === 0) && (
                <p className="text-center py-12 text-muted-foreground">Sem atividade recente registada.</p>
              )}
            </div>
            <div className="mt-6 pt-6 border-t border-border">
               <div className="bg-primary/5 rounded-xl p-4 border border-primary/10">
                  <h4 className="text-sm font-bold text-primary flex items-center gap-2 mb-1">
                     <TrendingUp className="h-4 w-4" />
                     Dica de Gestão
                  </h4>
                  <p className="text-xs text-muted-foreground leading-relaxed">
                     A média de satisfação global está em <span className="text-foreground font-bold">{data?.averageRating?.toFixed(1)} estrelas</span>. 
                     Os cursos de Segurança continuam a ter a maior taxa de conclusão.
                  </p>
               </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
