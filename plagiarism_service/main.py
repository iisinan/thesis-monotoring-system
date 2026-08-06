from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from typing import List, Optional
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity
import uvicorn

app = FastAPI(title="TMS Plagiarism Checker Microservice")

class DocumentCompareRequest(BaseModel):
    text: str
    corpus: List[str]
    threshold: float = 0.3

class PlagiarismResult(BaseModel):
    similarity: float
    is_plagiarized: bool
    matched_index: Optional[int] = None
    matched_text: Optional[str] = None

@app.get("/health")
def health():
    return {"status": "healthy"}

@app.post("/check", response_model=PlagiarismResult)
def check_plagiarism(req: DocumentCompareRequest):
    if not req.text.strip():
        raise HTTPException(status_code=400, detail="Target text cannot be empty.")
    if not req.corpus:
        return PlagiarismResult(similarity=0.0, is_plagiarized=False)
        
    try:
        # Standardize strings
        documents = [req.text] + req.corpus
        vectorizer = TfidfVectorizer().fit_transform(documents)
        vectors = vectorizer.toarray()
        
        # Calculate Cosine Similarity of the input document against all corpus docs
        input_vector = vectors[0:1]
        corpus_vectors = vectors[1:]
        
        similarities = cosine_similarity(input_vector, corpus_vectors)[0]
        
        max_sim = float(max(similarities))
        max_idx = int(similarities.argmax())
        
        return PlagiarismResult(
            similarity=max_sim,
            is_plagiarized=max_sim >= req.threshold,
            matched_index=max_idx if max_sim >= req.threshold else None,
            matched_text=req.corpus[max_idx] if max_sim >= req.threshold else None
        )
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Plagiarism calculation failed: {str(e)}")

if __name__ == "__main__":
    uvicorn.run(app, host="0.0.0.0", port=8001)
