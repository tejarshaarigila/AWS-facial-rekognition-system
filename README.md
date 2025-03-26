# AWS Facial Recognition System with Raspberry Pi

This repository implements a facial recognition system utilizing **Amazon Web Services (AWS)**, designed to operate on a **Raspberry Pi 4**. The system comprises two primary components:

1. **Portal**: A web-based interface developed using **PHP**, allowing clients to register by uploading facial images.
2. **Recognition**: Python scripts that run on the Raspberry Pi, enabling screening personnel to verify whether a user is registered by processing live video feeds.

## AWS Services Used

- **Amazon EC2**: Provides the web server to host the **PHP-based registration portal**.
- **Amazon S3**: Used to store the uploaded facial images.
- **Amazon Rekognition**: A powerful image and video analysis service used for **face detection**, **face matching**, and **indexing faces**.
- **Amazon DynamoDB**: Stores metadata associated with registered users, such as the external image ID, for easy retrieval during face verification.

## Features

- **Client Registration**: Clients can register by uploading facial images through the web portal.
- **Face Detection and Verification**: Screening personnel can verify registered clients by processing live video feeds using the recognition scripts running on the Raspberry Pi.
- **Real-Time Processing**: The system provides real-time facial recognition capabilities suitable for security, attendance, and user authentication.

## System Requirements

- **Hardware**: Raspberry Pi 4
- **Operating System**: Raspberry Pi OS
- **Python**: Version 3.8 or higher
- **PHP**: Version 7.4 or higher
- **AWS Account**: Access to the following AWS services:
  - **EC2** (for hosting the registration portal)
  - **S3** (for storing facial images)
  - **Rekognition** (for face detection and matching)
  - **DynamoDB** (for storing user data)

## Installation and Setup

### 1. Clone the Repository

Open the terminal on your Raspberry Pi and execute:

```bash
git clone https://github.com/tejarshaarigila/AWS-facial-rekognition-system.git
```

### 2. Set Up the Portal

- **Navigate to the Portal Directory**:

  ```bash
  cd AWS-facial-rekognition-system/portal
  ```

- **Configure AWS Credentials**:

  Set your AWS credentials and region by editing the `~/.aws/credentials` and `~/.aws/config` files, or by exporting environment variables:

  ```bash
  export AWS_ACCESS_KEY_ID=your_access_key
  export AWS_SECRET_ACCESS_KEY=your_secret_key
  export AWS_DEFAULT_REGION=your_region
  ```

- **Deploy the Portal**:

  Place the PHP files in your web server's root directory (e.g., `/var/www/html/` on EC2).

- **Access the Portal**:

  Open a web browser and navigate to `http://<raspberry_pi_ip>/portal/` to access the registration page. This page allows clients to upload facial images for registration.

### 3. Set Up the Recognition Scripts

- **Navigate to the Recognition Directory**:

  ```bash
  cd ../recognition
  ```

- **Install Required Python Packages**:

  Ensure you have `pip` installed, then install the necessary packages:

  ```bash
  pip3 install -r requirements.txt
  ```

- **Configure AWS Credentials**:

  Set your AWS credentials and region as described in the Portal setup.

- **Run the Recognition Script**:

  Execute the facial recognition script:

  ```bash
  python3 recognize_face.py
  ```

  This script captures video from the Raspberry Pi camera, detects faces, and verifies them against registered clients using **AWS Rekognition**.

### 4. AWS Services Configuration

#### Amazon S3: Facial Image Storage
- Create an S3 bucket (e.g., `face-template`) to store the uploaded client images.
- Ensure the appropriate S3 permissions are set to allow access by AWS Rekognition.

#### Amazon Rekognition: Face Detection & Indexing
- Create a **Rekognition Collection** (e.g., `templates`) to store and manage indexed faces.
- Use the **index_faces** function to add images from the S3 bucket to the Rekognition collection for future matching.

#### Amazon DynamoDB: Storing User Metadata
- Set up a **DynamoDB table** (e.g., `UserMetadata`) to store metadata such as usernames, external image IDs, and any other relevant information for registered users.
  
## Usage

### 1. Client Registration

- **Access the Registration Portal**:
  - Open the web portal at `http://<raspberry_pi_ip>/portal/`.
  - Clients can register by uploading a clear facial image.
  - The image is stored in **S3** and indexed in **Rekognition**.

### 2. Face Verification

- **Run the Recognition Script**:
  - Execute the `recognize_face.py` script on the Raspberry Pi.
  - The script captures live video feed, detects faces in real-time, and compares them to the indexed images in **AWS Rekognition**.
  - If a match is found, the script outputs the name of the registered user.

## Additional Notes

- **Performance Considerations**:
  - The **Raspberry Pi 4** provides sufficient power for real-time face recognition tasks, but performance may vary based on the complexity of the environment (e.g., lighting and number of faces).
  - **Rekognition** processes images in near real-time; however, the time taken for face detection and matching depends on the image quality and number of faces in the collection.

## Contributing

Contributions are welcome! Please fork the repository, create a new branch, and submit a pull request with your proposed changes.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## References

- [AWS Rekognition Documentation](https://docs.aws.amazon.com/rekognition/)
- [AWS SDK for Python (Boto3)](https://boto3.amazonaws.com/v1/documentation/api/latest/index.html)
- [Raspberry Pi 4 Model B Setup Guide](https://www.raspberrypi.org/documentation/setup/)
