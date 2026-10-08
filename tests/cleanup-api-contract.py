"""Check removed legacy routes through the local gateway."""
import urllib.request,urllib.error
base='http://localhost:8080'
paths=[
 '/api/registration/company/start','/api/registration/company/finish',
 '/api/registration/company/step/address','/api/registration/company/step/leader',
 '/api/registration/company/step/contact','/api/registration/company/step/bank-detail',
 '/api/registration/api/company','/api/registration/api/company/addresses',
 '/api/registration/api/company/leader','/api/registration/api/company/contact',
 '/api/registration/api/company/bank-detail','/api/registration/get-company-info',
 '/api/registration/send-code-channel','/api/registration/check-code-sms',
 '/api/registration/resend-code-sms','/api/registration/verify',
 '/auth/sign-in/request','/auth/user-set-company',
]
for path in paths:
 req=urllib.request.Request(base+path,data=b'{}',headers={'Content-Type':'application/json'})
 try:
  urllib.request.urlopen(req,timeout=10)
  raise AssertionError('Removed API still available: '+path)
 except urllib.error.HTTPError as e:
  assert e.code==404,(path,e.code)
print('PASS: removed company/SMS APIs return 404 through the gateway')
