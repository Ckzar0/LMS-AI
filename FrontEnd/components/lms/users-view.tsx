"use client"

import { useState } from "react"
import { Search, UserPlus, MoreVertical, Mail, BookOpen, Award, TrendingUp } from "lucide-react"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Badge } from "@/components/ui/badge"
import { Progress } from "@/components/ui/progress"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"

const users = [
  {
    id: "1",
    name: "João Silva",
    email: "joao.silva@empresa.com",
    department: "Produção",
    enrolledCourses: 3,
    completedCourses: 2,
    certifications: 2,
    avgScore: 92,
    status: "active"
  },
  {
    id: "2",
    name: "Maria Santos",
    email: "maria.santos@empresa.com",
    department: "Qualidade",
    enrolledCourses: 4,
    completedCourses: 3,
    certifications: 3,
    avgScore: 88,
    status: "active"
  },
  {
    id: "3",
    name: "Pedro Costa",
    email: "pedro.costa@empresa.com",
    department: "Logística",
    enrolledCourses: 2,
    completedCourses: 1,
    certifications: 1,
    avgScore: 85,
    status: "active"
  },
  {
    id: "4",
    name: "Ana Oliveira",
    email: "ana.oliveira@empresa.com",
    department: "Recursos Humanos",
    enrolledCourses: 5,
    completedCourses: 5,
    certifications: 5,
    avgScore: 95,
    status: "active"
  },
  {
    id: "5",
    name: "Rui Ferreira",
    email: "rui.ferreira@empresa.com",
    department: "Manutenção",
    enrolledCourses: 3,
    completedCourses: 1,
    certifications: 1,
    avgScore: 79,
    status: "active"
  },
  {
    id: "6",
    name: "Sofia Martins",
    email: "sofia.martins@empresa.com",
    department: "Produção",
    enrolledCourses: 2,
    completedCourses: 2,
    certifications: 2,
    avgScore: 94,
    status: "active"
  },
  {
    id: "7",
    name: "Miguel Alves",
    email: "miguel.alves@empresa.com",
    department: "Logística",
    enrolledCourses: 1,
    completedCourses: 0,
    certifications: 0,
    avgScore: null,
    status: "pending"
  },
]

const stats = [
  { label: "Total de Colaboradores", value: "156", icon: TrendingUp },
  { label: "Em Formação", value: "89", icon: BookOpen },
  { label: "Certificados", value: "67", icon: Award },
]

export function UsersView() {
  const [searchTerm, setSearchTerm] = useState("")

  const filteredUsers = users.filter(user =>
    user.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    user.email.toLowerCase().includes(searchTerm.toLowerCase()) ||
    user.department.toLowerCase().includes(searchTerm.toLowerCase())
  )

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-foreground">Colaboradores</h1>
          <p className="text-muted-foreground">Gerencie os colaboradores e o seu progresso de formação</p>
        </div>
        <Button className="gap-2">
          <UserPlus className="h-4 w-4" />
          Adicionar Colaborador
        </Button>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        {stats.map((stat) => {
          const Icon = stat.icon
          return (
            <Card key={stat.label}>
              <CardContent className="p-6">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm text-muted-foreground">{stat.label}</p>
                    <p className="text-3xl font-bold text-foreground mt-1">{stat.value}</p>
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

      {/* Search */}
      <div className="relative max-w-md">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input 
          placeholder="Pesquisar por nome, email ou departamento..." 
          className="pl-10"
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
        />
      </div>

      {/* Users List */}
      <Card>
        <CardContent className="p-0">
          <div className="divide-y divide-border">
            {filteredUsers.map((user) => (
              <div key={user.id} className="flex items-center gap-6 p-4 hover:bg-muted/50 transition-colors">
                <div className="h-12 w-12 rounded-full bg-primary/10 flex items-center justify-center text-primary font-medium">
                  {user.name.split(" ").map(n => n[0]).join("")}
                </div>
                
                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-2">
                    <span className="font-medium text-foreground">{user.name}</span>
                    <Badge variant={user.status === "active" ? "default" : "secondary"}>
                      {user.status === "active" ? "Ativo" : "Pendente"}
                    </Badge>
                  </div>
                  <div className="flex items-center gap-4 mt-1 text-sm text-muted-foreground">
                    <span className="flex items-center gap-1">
                      <Mail className="h-3 w-3" />
                      {user.email}
                    </span>
                    <span>{user.department}</span>
                  </div>
                </div>

                <div className="flex items-center gap-8">
                  <div className="text-center">
                    <p className="text-lg font-semibold text-foreground">{user.completedCourses}/{user.enrolledCourses}</p>
                    <p className="text-xs text-muted-foreground">Cursos</p>
                  </div>
                  <div className="text-center">
                    <p className="text-lg font-semibold text-foreground">{user.certifications}</p>
                    <p className="text-xs text-muted-foreground">Certificações</p>
                  </div>
                  <div className="text-center w-16">
                    {user.avgScore !== null ? (
                      <>
                        <p className="text-lg font-semibold text-foreground">{user.avgScore}%</p>
                        <p className="text-xs text-muted-foreground">Nota Média</p>
                      </>
                    ) : (
                      <p className="text-sm text-muted-foreground">-</p>
                    )}
                  </div>
                </div>

                <DropdownMenu>
                  <DropdownMenuTrigger asChild>
                    <Button variant="ghost" size="icon">
                      <MoreVertical className="h-4 w-4" />
                    </Button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent align="end">
                    <DropdownMenuItem>Ver Perfil</DropdownMenuItem>
                    <DropdownMenuItem>Inscrever em Curso</DropdownMenuItem>
                    <DropdownMenuItem>Enviar Email</DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem className="text-destructive">Desativar</DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>
    </div>
  )
}
