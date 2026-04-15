"use client"

import { useState } from "react"
import { Award, Download, Search, Calendar, User, BookOpen, CheckCircle2 } from "lucide-react"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Badge } from "@/components/ui/badge"

const certifications = [
  {
    id: "1",
    user: "João Silva",
    course: "Segurança no Trabalho - Normas ISO 45001",
    score: 92,
    issuedAt: "2024-02-10",
    expiresAt: "2025-02-10",
    certificateId: "CERT-2024-001",
    status: "active"
  },
  {
    id: "2",
    user: "Ana Oliveira",
    course: "Segurança no Trabalho - Normas ISO 45001",
    score: 95,
    issuedAt: "2024-02-08",
    expiresAt: "2025-02-08",
    certificateId: "CERT-2024-002",
    status: "active"
  },
  {
    id: "3",
    user: "Maria Santos",
    course: "Operação de Empilhadores",
    score: 88,
    issuedAt: "2024-01-25",
    expiresAt: "2025-01-25",
    certificateId: "CERT-2024-003",
    status: "active"
  },
  {
    id: "4",
    user: "Pedro Costa",
    course: "HACCP - Segurança Alimentar",
    score: 91,
    issuedAt: "2024-01-20",
    expiresAt: "2025-01-20",
    certificateId: "CERT-2024-004",
    status: "active"
  },
  {
    id: "5",
    user: "Rui Ferreira",
    course: "Primeiros Socorros no Local de Trabalho",
    score: 86,
    issuedAt: "2023-06-15",
    expiresAt: "2024-06-15",
    certificateId: "CERT-2023-089",
    status: "expired"
  },
  {
    id: "6",
    user: "Sofia Martins",
    course: "Operação de Empilhadores",
    score: 94,
    issuedAt: "2024-02-12",
    expiresAt: "2025-02-12",
    certificateId: "CERT-2024-005",
    status: "active"
  },
]

const stats = [
  { label: "Total de Certificações", value: "89", icon: Award },
  { label: "Certificações Ativas", value: "82", icon: CheckCircle2 },
  { label: "A Expirar (30 dias)", value: "7", icon: Calendar },
]

export function CertificationsView() {
  const [searchTerm, setSearchTerm] = useState("")
  const [filter, setFilter] = useState<"all" | "active" | "expired">("all")

  const filteredCertifications = certifications.filter(cert => {
    const matchesSearch = 
      cert.user.toLowerCase().includes(searchTerm.toLowerCase()) ||
      cert.course.toLowerCase().includes(searchTerm.toLowerCase())
    const matchesFilter = filter === "all" || cert.status === filter
    return matchesSearch && matchesFilter
  })

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-foreground">Certificações</h1>
        <p className="text-muted-foreground">Gerencie todas as certificações emitidas</p>
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

      {/* Filters */}
      <div className="flex items-center gap-4">
        <div className="relative flex-1 max-w-md">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input 
            placeholder="Pesquisar por colaborador ou curso..." 
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
            Todas
          </Button>
          <Button 
            variant={filter === "active" ? "default" : "outline"} 
            size="sm"
            onClick={() => setFilter("active")}
          >
            Ativas
          </Button>
          <Button 
            variant={filter === "expired" ? "default" : "outline"} 
            size="sm"
            onClick={() => setFilter("expired")}
          >
            Expiradas
          </Button>
        </div>
      </div>

      {/* Certifications List */}
      <Card>
        <CardContent className="p-0">
          <div className="divide-y divide-border">
            {filteredCertifications.map((cert) => (
              <div key={cert.id} className="flex items-center gap-6 p-4 hover:bg-muted/50 transition-colors">
                <div className="h-12 w-12 rounded-lg bg-primary/10 flex items-center justify-center">
                  <Award className="h-6 w-6 text-primary" />
                </div>
                
                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-2">
                    <User className="h-4 w-4 text-muted-foreground" />
                    <span className="font-medium text-foreground">{cert.user}</span>
                    <Badge variant={cert.status === "active" ? "default" : "secondary"}>
                      {cert.status === "active" ? "Ativa" : "Expirada"}
                    </Badge>
                  </div>
                  <div className="flex items-center gap-2 mt-1">
                    <BookOpen className="h-4 w-4 text-muted-foreground" />
                    <span className="text-sm text-muted-foreground">{cert.course}</span>
                  </div>
                </div>

                <div className="text-right">
                  <p className="text-sm font-medium text-foreground">Nota: {cert.score}%</p>
                  <p className="text-xs text-muted-foreground">{cert.certificateId}</p>
                </div>

                <div className="text-right">
                  <p className="text-sm text-muted-foreground">Emitido: {cert.issuedAt}</p>
                  <p className="text-xs text-muted-foreground">Expira: {cert.expiresAt}</p>
                </div>

                <Button variant="outline" size="sm" className="gap-2">
                  <Download className="h-4 w-4" />
                  PDF
                </Button>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>
    </div>
  )
}
