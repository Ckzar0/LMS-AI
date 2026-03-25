// Types for Moodle course generation

export interface QuestionAnswer {
  text: string
  fraction: number
  feedback: string
}

export interface MatchingSubquestion {
  text: string
  answer: string
}

export interface Question {
  name: string
  questiontext: string
  qtype: "multichoice" | "truefalse" | "matching"
  answers?: QuestionAnswer[]
  correctanswer?: boolean
  feedback?: string
  subquestions?: MatchingSubquestion[]
}

export interface QuestionBank {
  name: string
  questions: Question[]
}

export interface Activity {
  type: "page" | "quiz" | "lesson"
  name: string
  content?: string
  intro?: string
  questions_from_bank?: string | {
    bank_name: string;
    count: number;
  }
  timeopen?: number
  timeclose?: number
  timelimit?: number
  attempts?: number
  gradepass?: number
}

export interface MoodleCourse {
  course_name: string
  course_shortname: string
  source_file: string
  course_summary: string
  question_banks: QuestionBank[]
  activities: Activity[]
}

export interface GenerationConfig {
  courseName: string
  difficulty: "easy" | "medium" | "hard"
  depth: "flash" | "standard" | "deep"
  quizDuration: number
  numberOfQuestions: number
  generateVideos: boolean
  generateQuizzes: boolean
  generateCertificate: boolean
  divideInModules: boolean
}

export interface GenerationProgress {
  status: "idle" | "extracting" | "generating" | "sending" | "complete" | "error"
  progress: number
  message: string
  course?: MoodleCourse
  error?: string
}
