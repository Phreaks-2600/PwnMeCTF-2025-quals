terraform {
  required_version = ">= 1.0.0"
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
    random = {
      source  = "hashicorp/random"
      version = "~> 3.0"
    }
  }
}

provider "aws" {
  region = "us-east-1"
}

resource "aws_iam_openid_connect_provider" "github" {
  url             = "https://token.actions.githubusercontent.com"
  client_id_list  = ["sts.amazonaws.com"]
  thumbprint_list = ["6938fd4d98bab03faadb97b34396831e3780aea1"]
}

resource "aws_s3_bucket" "private_assets" {
  bucket        = "pwnme-private-assets"
  acl           = "private"
  force_destroy = true
}

resource "aws_s3_bucket" "public_assets" {
  bucket        = "pwnme-public-assets"
  force_destroy = true
}

resource "aws_s3_bucket_public_access_block" "public_assets" {
  bucket                  = aws_s3_bucket.public_assets.id
  block_public_acls       = false
  block_public_policy     = false
  ignore_public_acls      = false
  restrict_public_buckets = false
}

resource "aws_s3_bucket_ownership_controls" "public_assets" {
  bucket = aws_s3_bucket.public_assets.id

  rule {
    object_ownership = "ObjectWriter"
  }
}

resource "aws_s3_bucket_acl" "public_assets_acl" {
  depends_on = [aws_s3_bucket_ownership_controls.public_assets]
  bucket     = aws_s3_bucket.public_assets.id
  acl        = "public-read"
}

resource "random_integer" "role_suffix" {
  min = 100
  max = 999
}



resource "aws_iam_role" "github_actions_role" {
  name = "pwnme-github-role-${random_integer.role_suffix.result}"
  assume_role_policy = jsonencode({
    Version   = "2012-10-17",
    Statement = [
      {
        Effect    = "Allow",
        Principal = { Federated = aws_iam_openid_connect_provider.github.arn },
        Action    = "sts:AssumeRoleWithWebIdentity",
        Condition = {
          StringEquals = {
            "token.actions.githubusercontent.com:aud" = "sts.amazonaws.com"
          },
          StringLike = {
            "token.actions.githubusercontent.com:sub" = "repo:*/*pwnme-github-test:*"
          }
        }
      }
    ]
  })
}


resource "aws_iam_role_policy" "s3_read_policy" {
  name   = "pwnme-s3-read-policy"
  role   = aws_iam_role.github_actions_role.id
  policy = jsonencode({
    Version   = "2012-10-17",
    Statement = [
      {
        Effect   = "Allow",
        Action   = ["s3:ListBucket"],
        Resource = aws_s3_bucket.private_assets.arn
      },
      {
        Effect   = "Allow",
        Action   = ["s3:GetObject"],
        Resource = "${aws_s3_bucket.private_assets.arn}/*"
      }
    ]
  })
}

