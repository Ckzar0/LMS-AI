"use client"

import { 
  LayoutDashboard, 
  BookOpen, 
  Upload, 
  Award, 
  Users,
  Settings,
  HelpCircle,
  ExternalLink
} from "lucide-react"
import { UmainLogo } from "@/components/umain-logo"
import { cn } from "@/lib/utils"
import type { ViewType } from "@/app/page"

const MOODLE_URL = "http://localhost:8080";

interface SidebarProps {
  currentView: ViewType
  onViewChange: (view: ViewType) => void
}

const menuItems = [
  { id: "dashboard" as ViewType, label: "Dashboard", icon: LayoutDashboard },
  { id: "courses" as ViewType, label: "Cursos", icon: BookOpen },
  { id: "upload" as ViewType, label: "Gerar Curso", icon: Upload },
  { id: "certifications" as ViewType, label: "Certificações", icon: Award },
  { id: "users" as ViewType, label: "Colaboradores", icon: Users },
]

export function Sidebar({ currentView, onViewChange }: SidebarProps) {
  return (
    <aside className="w-64 bg-sidebar text-sidebar-foreground flex flex-col border-r border-sidebar-border">
      <div className="p-4 border-b border-sidebar-border">
        <UmainLogo variant="light" />
        <p className="text-xs text-sidebar-foreground/60 mt-1">Learning Management System</p>
      </div>

      <nav className="flex-1 p-3">
        <ul className="space-y-1">
          {menuItems.map((item) => {
            const Icon = item.icon
            const isActive = currentView === item.id
            return (
              <li key={item.id}>
                <button
                  onClick={() => onViewChange(item.id)}
                  className={cn(
                    "w-full flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium transition-colors",
                    isActive 
                      ? "bg-sidebar-primary text-sidebar-primary-foreground" 
                      : "text-sidebar-foreground/80 hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                  )}
                >
                  <Icon className="h-5 w-5" />
                  {item.label}
                </button>
              </li>
            )
          })}
        </ul>
      </nav>

      <div className="p-3 border-t border-sidebar-border">
        <ul className="space-y-1">
          <li>
            <a 
              href={MOODLE_URL} 
              target="_blank" 
              rel="noopener noreferrer"
              className="w-full flex items-center gap-3 px-3 py-2.5 rounded-md text-sm text-sidebar-foreground/60 hover:bg-sidebar-accent hover:text-sidebar-accent-foreground transition-colors"
            >
              <ExternalLink className="h-5 w-5" />
              Administração Moodle
            </a>
          </li>
          <li>
            <button className="w-full flex items-center gap-3 px-3 py-2.5 rounded-md text-sm text-sidebar-foreground/60 hover:bg-sidebar-accent hover:text-sidebar-accent-foreground transition-colors">
              <Settings className="h-5 w-5" />
              Definições
            </button>
          </li>
          <li>
            <button className="w-full flex items-center gap-3 px-3 py-2.5 rounded-md text-sm text-sidebar-foreground/60 hover:bg-sidebar-accent hover:text-sidebar-accent-foreground transition-colors">
              <HelpCircle className="h-5 w-5" />
              Ajuda
            </button>
          </li>
        </ul>
      </div>

      <div className="p-4 border-t border-sidebar-border">
        <div className="text-xs text-sidebar-foreground/50">
          <p>Powered by IBM watsonx</p>
          <p className="mt-1">© 2024 Umain Works</p>
        </div>
      </div>
    </aside>
  )
}
