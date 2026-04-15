import type { Metadata } from 'next'
import { Inter } from 'next/font/google'
import './globals.css'

const inter = Inter({ subsets: ["latin"], variable: "--font-inter" });

export const metadata: Metadata = {
  title: 'LMS AI - Umain Works',
  description: 'Sistema de Gestão de Aprendizagem Inteligente e Personalizado powered by Umain Works',
  generator: 'Umain Works',
  icons: {
    icon: [
      {
        url: '/umain_icon.png',
        type: 'image/png',
      },
    ],
    apple: '/umain_icon.png',
  },
}

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode
}>) {
  return (
    <html lang="en">
      <body className={`${inter.variable} font-sans antialiased`}>
        {children}
      </body>
    </html>
  )
}
