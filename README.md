# Ergovision: AI-Powered Real-Time Posture Corrector

![Ergovision Banner](https://via.placeholder.com/1200x400?text=ERGOVISION+AI+POSTURE+ENGINE)

**Ergovision** is a web-based, real-time ergonomic monitoring system designed to detect and correct poor sitting posture. By leveraging a **Hybrid Machine Learning Architecture** (Google MediaPipe + Custom Random Forest Classifier), the system analyzes biomechanical angles via a standard webcam to provide instant, adaptive feedback without requiring specialized hardware.



---

## 🚀 Key Features

* **Real-Time Pose Estimation:** Uses MediaPipe BlazePose to extract 33 skeletal landmarks directly in the browser.
* **Hybrid AI Classification:** A custom **Random Forest Classifier** (`ergovision_final_model.pkl`) trained on doctor-validated data to distinguish between "Neutral" and "Slouching" states with **91.64% accuracy**.
* **Angle-Based Scoring Engine:** Calculates clinical biomechanical metrics (Neck Inclination, Trunk Flexion) to generate a granular 0-100% posture score.
* **Adaptive Feedback Loop:** State-based intervention logic (Warning → Alert → Critical) prevents alert fatigue by only triggering notifications after sustained poor posture.
* **Session Analytics:** Tracks long-term ergonomic health with session efficiency scores, slouch duration, and trend analysis.
* **Privacy-First Design:** Video feeds are processed locally or via a secure microservice; raw video is never stored.

---

## 🛠️ Tech Stack

**Frontend (Client-Side)**
* **Vue.js 3:** Reactive UI framework for the real-time dashboard.
* **Tailwind CSS:** Utility-first styling for a responsive, modern interface.
* **Inertia.js:** Seamless glue between the Laravel backend and Vue frontend.

**Backend (Orchestrator)**
* **Laravel 10 (PHP):** REST API, user authentication (Breeze), and database management.
* **MySQL:** Relational database for storing user profiles, session logs, and historical metrics.

**AI Microservice (The "Brain")**
* **Python 3.10 + Flask:** Lightweight API server for inference.
* **Google MediaPipe:** Computer vision framework for landmark extraction.
* **Scikit-Learn:** Machine learning library for the Random Forest classifier.
* **NumPy:** Vector mathematics for angular calculations.

---

## ⚙️ Installation & Setup

### Prerequisites
* Node.js & NPM
* PHP >= 8.1 & Composer
* Python >= 3.9
* MySQL Server
