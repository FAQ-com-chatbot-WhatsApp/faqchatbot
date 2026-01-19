
from redis import Redis
from rq import Queue, Worker
from rq.registry import FailedJobRegistry

redis = Redis(host='localhost', port=6379, db=0)
q = Queue('messages', connection=redis)
registry = FailedJobRegistry(queue=q)

for job_id in registry.get_job_ids():
    job = q.fetch_job(job_id)
    print(f"Job ID: {job_id}")
    print(f"Function: {job.func_name}")
    print(f"Exception: {job.exc_info}")
    print("-" * 20)
