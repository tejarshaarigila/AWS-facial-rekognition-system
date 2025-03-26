import boto3
import re
import logging

def sanitize_external_image_id(image_id):
    return re.sub(r"@.*", "", image_id)

def list_images(bucket_name):
    s3_client = boto3.client('s3')
    try:
        response = s3_client.list_objects_v2(Bucket=bucket_name)
        image_keys = [item['Key'] for item in response['Contents'] if '/photo_' in item['Key']]
        return image_keys
    except boto3.exceptions.S3UploadFailedError as e:
        logging.error(f"Error listing objects in bucket: {e}")
        return []

def index_face(collection_id, bucket, image_key, username):
    rekognition_client = boto3.client('rekognition', region_name='us-east-2')
    try:
        response = rekognition_client.index_faces(
            CollectionId=collection_id,
            Image={'S3Object': {'Bucket': bucket, 'Name': image_key}},
            ExternalImageId=username,
            MaxFaces=1,
            QualityFilter="AUTO",
            DetectionAttributes=['ALL']
        )
        return response
    except boto3.exceptions.S3UploadFailedError as e:
        logging.error(f"Error uploading image to Rekognition: {e}")
    except Exception as e:
        logging.error(f"Error indexing face: {e}")
    return None

def process_images(bucket_name, collection_id):
    image_keys = list_images(bucket_name)
    for key in image_keys:
        username = key.split('/')[0]
        sanitized_username = sanitize_external_image_id(username)
        response = index_face(collection_id, bucket_name, key, sanitized_username)
        if response:
            logging.info(f"Indexed {key} with username {sanitized_username}: {response}")
        else:
            logging.error(f"Failed to index {key}")

if __name__ == "__main__":
    bucket_name = 'face-template'
    collection_id = 'templates'
    process_images(bucket_name, collection_id)
