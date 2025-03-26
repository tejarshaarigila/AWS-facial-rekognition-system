import boto3
import cv2
import tkinter as tk
from PIL import Image, ImageTk
import logging
import time

class FacialRecognitionApp:
    def __init__(self, master):
        self.master = master
        self.master.title("Facial Recognition System")
        self.cap = cv2.VideoCapture(0)
        self.frame_captured = None
        self.client = self.create_aws_client()
        self.last_capture_time = 0
        self.capture_interval = 2  # Limit recognition to every 2 seconds
        
        self.display_label = tk.Label(self.master)
        self.display_label.pack(side='top', fill='both', expand=True)

        self.capture_button = tk.Button(self.master, text="Capture", command=self.on_capture)
        self.capture_button.pack(side='left', fill='x', expand=True)

        self.result_label = tk.Label(self.master, text="")
        self.result_label.pack(side='right', fill='x', expand=True)

        self.update_frame()

    def create_aws_client(self):
        return boto3.client('rekognition', region_name='us-east-2')

    def search_faces_by_image(self, collection_id, image_bytes):
        try:
            response = self.client.search_faces_by_image(
                CollectionId=collection_id,
                Image={'Bytes': image_bytes},
                MaxFaces=1,
                FaceMatchThreshold=90
            )
            return response
        except boto3.exceptions.S3UploadFailedError as e:
            logging.error(f"Error in uploading image: {e}")
        except Exception as e:
            logging.error(f"Error in searching faces: {e}")
        return None

    def extract_username(self, face_matches):
        if face_matches:
            return face_matches[0]['Face']['ExternalImageId']
        return None

    def load_image_from_frame(self, frame):
        ret, buffer = cv2.imencode('.jpg', frame)
        return buffer.tobytes()

    def process_frame(self, frame):
        image_bytes = self.load_image_from_frame(frame)
        return self.search_faces_by_image('templates', image_bytes)

    def on_capture(self):
        current_time = time.time()
        if current_time - self.last_capture_time > self.capture_interval:
            if self.frame_captured:
                response = self.process_frame(self.frame_captured)
                if response:
                    face_matches = response.get('FaceMatches', [])
                    username = self.extract_username(face_matches)
                    self.display_result(username)
                else:
                    self.result_label.config(text='No Face Found')
                self.last_capture_time = current_time
            else:
                self.result_label.config(text='No frame captured')

    def display_result(self, username):
        if username:
            self.result_label.config(text=f'Enrolled Person: {username}')
        else:
            self.result_label.config(text='Person Not Enrolled / Match Not Found')

    def update_frame(self):
        ret, frame = self.cap.read()
        if ret:
            self.frame_captured = frame.copy()
            cv2image = cv2.cvtColor(frame, cv2.COLOR_BGR2RGBA)
            img = Image.fromarray(cv2image)
            imgtk = ImageTk.PhotoImage(image=img)
            self.display_label.imgtk = imgtk
            self.display_label.configure(image=imgtk)
        self.master.after(10, self.update_frame)

    def __del__(self):
        if self.cap.isOpened():
            self.cap.release()

    def run(self):
        self.master.mainloop()

if __name__ == "__main__":
    logging.basicConfig(level=logging.INFO)
    root = tk.Tk()
    app = FacialRecognitionApp(root)
    app.run()
