from django.db import models

class Photo(models.Model):
    file_name   = models.CharField(max_length=255)
    description = models.TextField()
    created_at  = models.DateTimeField()
    updated_at  = models.DateTimeField()
