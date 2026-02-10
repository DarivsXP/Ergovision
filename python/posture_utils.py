import numpy as np
import mediapipe as mp

def get_landmark(landmarks, part_name):
    """ Retrieves (x, y, z) coordinates if visibility > 0.5 """
    try:
        lm = landmarks[part_name.value]
        # Visibility check: If the camera can't see it well, ignore it
        if lm.visibility < 0.5: return None
        return np.array([lm.x, lm.y, lm.z])
    except:
        return None

def calculate_angle_between_vectors(v1, v2):
    """ Calculates angle in degrees between two vectors """
    # Normalize vectors to unit length
    v1_u = v1 / np.linalg.norm(v1)
    v2_u = v2 / np.linalg.norm(v2)

    # Dot product gives cosine of angle
    dot_prod = np.dot(v1_u, v2_u)

    # Clamp to handle floating point errors slightly outside [-1, 1]
    dot_prod = np.clip(dot_prod, -1.0, 1.0)

    return np.degrees(np.arccos(dot_prod))

def get_posture_angles(landmarks, image_size=(640, 480)):
    """
    Calculates the 3 critical angles for posture analysis.
    Args:
        landmarks: MediaPipe landmarks
        image_size: tuple (width, height) to correct aspect ratio distortion
    """
    mp_pose = mp.solutions.pose.PoseLandmark
    w, h = image_size

    # 1. Get Coordinates (Try Left first, then Right)
    # We retrieve raw normalized coordinates first
    hip_raw = get_landmark(landmarks, mp_pose.LEFT_HIP)
    shoulder_raw = get_landmark(landmarks, mp_pose.LEFT_SHOULDER)
    ear_raw = get_landmark(landmarks, mp_pose.LEFT_EAR)

    # If Left side is missing any point, try Right Side
    if hip_raw is None or shoulder_raw is None or ear_raw is None:
        hip_raw = get_landmark(landmarks, mp_pose.RIGHT_HIP)
        shoulder_raw = get_landmark(landmarks, mp_pose.RIGHT_SHOULDER)
        ear_raw = get_landmark(landmarks, mp_pose.RIGHT_EAR)

    # If we STILL can't see the key points, we can't judge posture
    if hip_raw is None or shoulder_raw is None or ear_raw is None:
        return { "torso_recline": None, "neck_protraction": None, "back_curve": None }

    # 2. Aspect Ratio Correction (CRITICAL FIX)
    # We must convert normalized [0,1] coords to real pixel-scale units
    # MediaPipe Z scale is roughly same as X scale.
    def to_real_world(lm_array):
        return np.array([lm_array[0] * w, lm_array[1] * h, lm_array[2] * w])

    hip = to_real_world(hip_raw)
    shoulder = to_real_world(shoulder_raw)
    ear = to_real_world(ear_raw)

    # 3. Define Vectors
    # Vertical Vector: Points straight UP in Y axis (0, -1, 0)
    # Note: Y decreases going up in image/pixel coords
    vec_vertical = np.array([0, -1, 0])

    # Vector: Torso (Hip -> Shoulder)
    vec_torso = shoulder - hip

    # Vector: Neck (Shoulder -> Ear)
    vec_neck = ear - shoulder

    # Vector: Back Curve Components
    vec_shoulder_to_ear = ear - shoulder
    vec_shoulder_to_hip = hip - shoulder

    # 4. Calculate Angles

    # A. Torso Recline: Angle between Vertical and Torso Vector
    angle_torso = calculate_angle_between_vectors(vec_vertical, vec_torso)

    # B. Neck Protraction: Angle between Vertical and Neck Vector
    angle_neck = calculate_angle_between_vectors(vec_vertical, vec_neck)

    # C. Back Curve: Angle between Neck and Torso
    angle_back = calculate_angle_between_vectors(vec_shoulder_to_ear, vec_shoulder_to_hip)

    return {
        "torso_recline": angle_torso,
        "neck_protraction": angle_neck,
        "back_curve": angle_back
    }
