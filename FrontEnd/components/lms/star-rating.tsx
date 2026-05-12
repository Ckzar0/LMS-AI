"use client"

import { Star, StarHalf } from "lucide-react"
import { cn } from "@/lib/utils"

interface StarRatingProps {
  rating: number
  maxRating?: number
  size?: "sm" | "md" | "lg"
  showValue?: boolean
  totalReviews?: number
  interactive?: boolean
  readonly?: boolean // Added for explicit clarity
  onChange?: (rating: number) => void
}

export function StarRating({ 
  rating, 
  maxRating = 5, 
  size = "md", 
  showValue = false, // Changed default to false to be cleaner in cards
  totalReviews,
  interactive = false,
  readonly = true,
  onChange
}: StarRatingProps) {
  const sizeClasses = {
    sm: "h-3 w-3",
    md: "h-4 w-4",
    lg: "h-5 w-5"
  }

  const textSizeClasses = {
    sm: "text-[10px]",
    md: "text-xs",
    lg: "text-sm"
  }

  const handleClick = (value: number) => {
    if (interactive && !readonly && onChange) {
      onChange(value)
    }
  }

  const isInteractive = interactive && !readonly;

  return (
    <div className="flex items-center gap-1">
      <div className="flex items-center">
        {Array.from({ length: maxRating }).map((_, index) => {
          const starValue = index + 1
          const filled = starValue <= Math.floor(rating)
          const isHalf = !filled && starValue <= Math.ceil(rating) && rating % 1 !== 0

          return (
            <button
              key={index}
              type="button"
              disabled={!isInteractive}
              onClick={() => handleClick(starValue)}
              className={cn(
                "relative flex items-center justify-center",
                isInteractive && "cursor-pointer hover:scale-110 transition-transform"
              )}
            >
              <Star
                className={cn(
                  sizeClasses[size],
                  filled 
                    ? "fill-amber-400 text-amber-400" 
                    : "fill-muted text-muted-foreground/20",
                  !filled && "absolute"
                )}
              />
              {isHalf && (
                <div className="relative overflow-hidden" style={{ width: '50%' }}>
                   <Star className={cn(sizeClasses[size], "fill-amber-400 text-amber-400")} />
                </div>
              )}
            </button>
          )
        })}
      </div>
      {showValue && (
        <span className={cn("text-muted-foreground font-medium", textSizeClasses[size])}>
          {rating.toFixed(1)}
          {totalReviews !== undefined && (
            <span className="text-muted-foreground/60 ml-0.5">({totalReviews})</span>
          )}
        </span>
      )}
    </div>
  )
}
