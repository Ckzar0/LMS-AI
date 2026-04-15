"use client"

import { Star } from "lucide-react"
import { cn } from "@/lib/utils"

interface StarRatingProps {
  rating: number
  maxRating?: number
  size?: "sm" | "md" | "lg"
  showValue?: boolean
  totalReviews?: number
  interactive?: boolean
  onChange?: (rating: number) => void
}

export function StarRating({ 
  rating, 
  maxRating = 5, 
  size = "md", 
  showValue = true,
  totalReviews,
  interactive = false,
  onChange
}: StarRatingProps) {
  const sizeClasses = {
    sm: "h-3 w-3",
    md: "h-4 w-4",
    lg: "h-5 w-5"
  }

  const textSizeClasses = {
    sm: "text-xs",
    md: "text-sm",
    lg: "text-base"
  }

  const handleClick = (value: number) => {
    if (interactive && onChange) {
      onChange(value)
    }
  }

  return (
    <div className="flex items-center gap-1">
      <div className="flex items-center">
        {Array.from({ length: maxRating }).map((_, index) => {
          const starValue = index + 1
          const filled = starValue <= rating
          const halfFilled = starValue > rating && starValue - 0.5 <= rating

          return (
            <button
              key={index}
              type="button"
              disabled={!interactive}
              onClick={() => handleClick(starValue)}
              className={cn(
                "relative",
                interactive && "cursor-pointer hover:scale-110 transition-transform"
              )}
            >
              <Star
                className={cn(
                  sizeClasses[size],
                  filled 
                    ? "fill-amber-400 text-amber-400" 
                    : halfFilled 
                      ? "fill-amber-400/50 text-amber-400" 
                      : "fill-muted text-muted-foreground/30"
                )}
              />
            </button>
          )
        })}
      </div>
      {showValue && (
        <span className={cn("text-muted-foreground ml-1", textSizeClasses[size])}>
          {rating.toFixed(1)}
          {totalReviews !== undefined && (
            <span className="text-muted-foreground/70"> ({totalReviews})</span>
          )}
        </span>
      )}
    </div>
  )
}
