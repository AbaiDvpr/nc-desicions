from contextlib import asynccontextmanager

from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

from app import models  # noqa: F401
from app.admin_seed import ensure_admin
from app.bp_jobs import recover_jobs
from app.bp_templates import ensure_bp
from app.database import SessionLocal, ensure_schema
from app.routers import admin, auth, bp, directory, fem_rates, fin_models, gov_programs, payments, profile, tariffs


@asynccontextmanager
async def lifespan(_: FastAPI):
    ensure_schema()
    ensure_admin()
    db = SessionLocal()
    try:
        ensure_bp(db)
        recover_jobs(db)
    finally:
        db.close()
    yield


app = FastAPI(title="NC-Desicion API", lifespan=lifespan)

app.add_middleware(
    CORSMiddleware,
    allow_origins=[
        "http://localhost:5173",
        "http://127.0.0.1:5173",
        "http://localhost:5174",
        "http://127.0.0.1:5174",
        "http://89.126.194.68.sslip.io",
        "http://admin.89.126.194.68.sslip.io",
        "https://89.126.194.68.sslip.io",
        "https://admin.89.126.194.68.sslip.io",
    ],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

app.include_router(auth.router)
app.include_router(admin.router)
app.include_router(bp.client)
app.include_router(bp.admin)
app.include_router(profile.router)
app.include_router(fin_models.router)
app.include_router(directory.router)
app.include_router(tariffs.router)
app.include_router(gov_programs.router)
app.include_router(gov_programs.admin)
app.include_router(fem_rates.client)
app.include_router(fem_rates.admin)
app.include_router(payments.router)


@app.get("/api/health")
def health():
    return {"status": "ok"}
