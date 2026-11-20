import React from 'react'
import Badge from "@/Components/UI/Badge";

function AnswerStatus({ question }) {
  if (!question.is_answered) {
          return <Badge variant="neutral">Tidak dijawab</Badge>;
      }
  
      if (question.is_correct === true) {
          return <Badge variant="success">Benar</Badge>;
      }
  
      if (question.is_correct === false) {
          return <Badge variant="error">Salah</Badge>;
      }
  
      return <Badge variant="warning">Belum dinilai</Badge>;
}

export default AnswerStatus