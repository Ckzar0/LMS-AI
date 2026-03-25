export function UmainLogo({ className = "", variant = "light" }: { className?: string; variant?: "light" | "dark" }) {
  const textColor = variant === "light" ? "text-white" : "text-sidebar"
  
  return (
    <div className={`flex items-baseline ${className}`}>
      <span className={`text-2xl font-bold tracking-tight ${textColor}`}>UMAIN</span>
      <span className={`text-lg font-light tracking-wide ${textColor} opacity-80`}>WORKS</span>
    </div>
  )
}

export function UmainLogoIcon({ className = "" }: { className?: string }) {
  return (
    <div className={`flex items-center justify-center w-8 h-8 rounded-md bg-primary text-primary-foreground font-bold text-sm ${className}`}>
      A<span className="text-xs align-super">i</span>
    </div>
  )
}
