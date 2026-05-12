"use client"

import { useState, useEffect } from "react"
import { Award, Download, Search, Calendar, User, BookOpen, CheckCircle2, Loader2 } from "lucide-react"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Badge } from "@/components/ui/badge"
import { toast } from "sonner"

interface Certification {
  course_name: string
  user_name: string
  date: number
  cmid: number
}

export function CertificationsView() {
  const [searchTerm, setSearchTerm] = useState("")
  const [certifications, setCertifications] = useState<Certification[]>([])
  const [loading, setLoading] = useState(true)
  const [downloading, setDownloading] = useState<number | null>(null)

  useEffect(() => {
    async function fetchCertifications() {
      try {
        const response = await fetch('/api/course/all-certificates')
        const data = await response.json()
        if (Array.isArray(data)) {
          setCertifications(data)
        }
      } catch (error) {
        console.error("Failed to fetch certifications", error)
        toast.error("Erro ao carregar arquivo de certificados")
      } finally {
        setLoading(false)
      }
    }
    fetchCertifications()
  }, [])

  const filteredCertifications = certifications.filter(cert => {
    const matchesSearch = 
      cert.user_name.toLowerCase().includes(searchTerm.toLowerCase()) ||
      cert.course_name.toLowerCase().includes(searchTerm.toLowerCase())
    return matchesSearch
  })

  const handleDownload = async (cmid: number, courseName: string, userName: string) => {
    if (cmid === 0) {
      toast.error("Este curso não tem certificado configurado.")
      return
    }

    setDownloading(cmid)
    try {
      const response = await fetch(`/api/course/certificate/${cmid}`)
      const data = await response.json()

      if (data.pdf) {
        const linkSource = `data:application/pdf;base64,${data.pdf}`
        const downloadLink = document.createElement("a")
        const fileName = `Certificado_${courseName.replace(/\s+/g, '_')}_${userName.replace(/\s+/g, '_')}.pdf`

        downloadLink.href = linkSource
        downloadLink.download = fileName
        downloadLink.click()
        toast.success("Certificado descarregado com sucesso!")
      } else {
        throw new Error("PDF not found")
      }
    } catch (err) {
      console.error("Download error:", err)
      toast.error("Erro ao gerar o PDF do certificado.")
    } finally {
      setDownloading(null)
    }
  }

  if (loading) {
    return (
      <div className="flex flex-col items-center justify-center h-64 gap-4">
        <Loader2 className="h-8 w-8 animate-spin text-primary" />
        <p className="text-muted-foreground">A carregar arquivo de certificados...</p>
      </div>
    )
  }

  const stats = [
    { label: "Total de Certificações", value: certifications.length.toString(), icon: Award },
    { label: "Colaboradores Únicos", value: new Set(certifications.map(c => c.user_name)).size.toString(), icon: CheckCircle2 },
    { label: "Última Emissão", value: certifications.length > 0 ? new Date(certifications[0].date * 1000).toLocaleDateString('pt-PT') : "N/A", icon: Calendar },
  ]

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-foreground">Arquivo de Certificações</h1>
        <p className="text-muted-foreground">Repositório centralizado de todos os certificados emitidos pela IA</p>
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
      </div>

      {/* Certifications List */}
      <Card>
        <CardContent className="p-0">
          <div className="divide-y divide-border">
            {filteredCertifications.map((cert, idx) => (
              <div key={idx} className="flex items-center gap-6 p-4 hover:bg-muted/50 transition-colors">
                <div className="h-12 w-12 rounded-lg bg-primary/10 flex items-center justify-center">
                  <Award className="h-6 w-6 text-primary" />
                </div>
                
                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-2">
                    <User className="h-4 w-4 text-muted-foreground" />
                    <span className="font-medium text-foreground">{cert.user_name}</span>
                    <Badge variant="outline" className="bg-green-50 text-green-700 border-green-200">
                      Emitido
                    </Badge>
                  </div>
                  <div className="flex items-center gap-2 mt-1">
                    <BookOpen className="h-4 w-4 text-muted-foreground" />
                    <span className="text-sm text-muted-foreground truncate">{cert.course_name}</span>
                  </div>
                </div>

                <div className="text-right">
                  <p className="text-sm text-muted-foreground">Emissão</p>
                  <p className="text-xs font-medium text-foreground">{new Date(cert.date * 1000).toLocaleString('pt-PT')}</p>
                </div>

                <Button 
                  variant="outline" 
                  size="sm" 
                  className="gap-2"
                  onClick={() => handleDownload(cert.cmid, cert.course_name, cert.user_name)}
                  disabled={downloading === cert.cmid}
                >
                  {downloading === cert.cmid ? (
                    <Loader2 className="h-4 w-4 animate-spin" />
                  ) : (
                    <Download className="h-4 w-4" />
                  )}
                  Descarregar
                </Button>
              </div>
            ))}
            {filteredCertifications.length === 0 && (
              <div className="p-12 text-center text-muted-foreground">
                Nenhum certificado encontrado no arquivo.
              </div>
            )}
          </div>
        </CardContent>
      </Card>
    </div>
  )
}
