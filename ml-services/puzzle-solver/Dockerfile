FROM python:3.11-slim

WORKDIR /app

# Install system deps for OpenCV
RUN apt-get update && apt-get install -y --no-install-recommends \
    libgl1-mesa-glx libglib2.0-0 \
    && rm -rf /var/lib/apt/lists/*

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY . .

# Create model directory
RUN mkdir -p model

EXPOSE 5050

CMD ["gunicorn", "-w", "2", "-b", "0.0.0.0:5050", "--timeout", "60", "app:app"]
