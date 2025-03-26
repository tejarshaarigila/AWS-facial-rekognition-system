import boto3
import logging

def list_indexed_faces(collection_id):
    rekognition_client = boto3.client('rekognition', region_name='us-east-2')
    try:
        response = rekognition_client.list_faces(CollectionId=collection_id)
        return response['Faces']
    except Exception as e:
        logging.error(f"Error listing faces: {e}")
        return []

if __name__ == "__main__":
    indexed_faces = list_indexed_faces('templates')
    if indexed_faces:
        for face in indexed_faces:
            logging.info(f"Face: {face}")
    else:
        logging.info("No indexed faces found.")
