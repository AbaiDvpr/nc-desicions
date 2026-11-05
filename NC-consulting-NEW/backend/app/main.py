from contextlib import asynccontextmanager

from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

from app import models  # noqa: F401
from app.admin_seed import ensure_admin
from app.database import ensure_schema
from app.routers import admin, auth, directory, fin_models, payments, profile, tariffs


@asynccontextmanager
async def lifespan(_: FastAPI):
    ensure_schema()
    ensure_admin()
    yield


app = FastAPI(title="NC-Desicion API", lifespan=lifespan)

app.add_middleware(
    CORSMiddleware,
    allow_origins=[
        "http://localhost:5173",
        "http://127.0.0.1:5173",
        "http://localhost:5174",
        "http://127.0.0.1:5174",
    ],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

app.include_router(auth.router)
app.include_router(admin.router)
app.include_router(profile.router)
app.include_router(fin_models.router)
app.include_router(directory.router)
app.include_router(tariffs.router)
app.include_router(payments.router)


@app.get("/api/health")
def health():
    return {"status": "ok"}
