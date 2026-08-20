# Firebase MFA: decisión de arquitectura pendiente

## Por qué no se agregó una verificación simulada

Firebase TOTP MFA requiere Firebase Authentication with Identity Platform, un
usuario autenticado por un primer factor compatible dentro de Firebase y un
email verificado. Los tokens personalizados no admiten MFA. Por eso no es
seguro validar la contraseña únicamente en PHP y luego enviar un código TOTP
aislado a Firebase como si fuera un segundo factor.

Documentación oficial:

- https://firebase.google.com/docs/auth/web/totp-mfa
- https://firebase.google.com/docs/auth/web/multi-factor

## Arquitectura Firebase técnicamente compatible

1. Actualizar el proyecto a Firebase Authentication with Identity Platform.
2. Habilitar Email/Password y TOTP MFA.
3. Agregar y verificar un email para cada usuario.
4. Crear la identidad correspondiente en Firebase.
5. Agregar a `usuarios` un `firebase_uid` único y nullable durante la transición.
6. Autenticar el primer factor y el TOTP en Firebase.
7. Enviar el ID token resultante al backend PHP mediante HTTPS.
8. Verificar el ID token en el servidor con credenciales fuera del repositorio.
9. Buscar `firebase_uid` en MariaDB y tomar exclusivamente de MariaDB el rol y
   los permisos.
10. Crear la sesión PHP final con `mfa_verified = true` y regenerar su ID.

Esta alternativa mueve la comprobación del primer factor a Firebase. No puede
mantener simultáneamente la contraseña PHP como único primer factor sin pedir
otra credencial o construir un proveedor de identidad compatible.

## Alternativa que conserva el login PHP

Implementar TOTP directamente en el backend PHP con una biblioteca RFC 6238,
secretos cifrados en reposo, códigos de recuperación y control de intentos.
Esta opción conserva MariaDB como primer factor, pero no utiliza Firebase.

## Datos y configuración todavía necesarios

- Proyecto Firebase e ID del proyecto.
- Aplicación web registrada y dominios autorizados.
- Upgrade a Identity Platform.
- Proveedor Email/Password habilitado.
- TOTP MFA habilitado.
- Email real y verificado para cada usuario.
- Decisión explícita entre migrar el primer factor a Firebase o usar TOTP PHP.
- Credenciales de servidor configuradas fuera del repositorio.

No se agregó `firebase_uid`, email ni configuración pública porque todavía no
existe una identidad Firebase con la cual vincular esos campos.
