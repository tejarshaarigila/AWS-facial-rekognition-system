import boto3
import logging

def empty_rekognition_collection(collection_id):
    rekognition_client = boto3.client('rekognition', region_name='us-east-2')

    try:
        listed_faces = rekognition_client.list_faces(CollectionId=collection_id)
        face_ids = [face['FaceId'] for face in listed_faces['Faces']]

        if face_ids:
            rekognition_client.delete_faces(CollectionId=collection_id, FaceIds=face_ids)
            logging.info(f"Deleted {len(face_ids)} faces from collection '{collection_id}'.")
        else:
            logging.info(f"No faces to delete in collection '{collection_id}'.")
    except Exception as e:
        logging.error(f"Error deleting faces: {e}")

if __name__ == "__main__":
    collection_id = 'templates'
    empty_rekognition_collection(collection_id)
