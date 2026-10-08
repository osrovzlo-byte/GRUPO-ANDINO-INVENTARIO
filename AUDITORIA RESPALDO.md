# Auditoría del respaldo de inventario

**Resultado: no recomendado para importar tal cual como base de trabajo.**

Archivo: `RESPALDO JSON.json`. Exportación declarada: 2026-10-07 14:42:10. Revisión: 2026-10-07.
SHA-256: `9186e8ca5478fad11a07540d68755556d1f487e361ec99b9ef746a80ade1d948`. El respaldo original y la aplicación no se modificaron.

## Contenido y comprobaciones correctas

| Sección | Registros |
|---|---:|
| inventory | 979 |
| agencies | 681 |
| movements | 1327 |
| maintenanceLog | 56 |
| categories | 26 |

JSON válido, sin claves repetidas dentro de objetos. Los campos críticos están presentes y tienen tipos compatibles con las funciones revisadas. No hay IDs de movimiento ni IDs de mantenimiento repetidos; los IDs de movimiento son válidos para documentos de Firebase. Las fechas comprobadas son válidas. Las categorías de los equipos están incluidas en el catálogo.

## 1. Identificadores de agencias en conflicto

13 grupos tienen ID repetido, con 16 filas adicionales que comparten identidad. 6 grupos tienen código repetido. No hay nombres exactos repetidos después de ignorar espacios externos y mayúsculas; varios nombres diferentes parecen corresponder a versiones antiguas o nuevas de la misma agencia, pero esa equivalencia requiere confirmación.

Ejemplo: `LA` identifica cinco filas, incluyendo nombres distintos de agencias. Por eso no es correcto borrar automáticamente todas las filas salvo una.

| ID compartido | Filas del respaldo, numeradas desde 1 | Nombres |
|---|---|---|
| AG-103 | 3, 664 | Departamento Técnico / Departamento T cnico |
| LA | 9, 23, 665, 667, 668 | AG LA MONTAÑITA  COPA DE ORO / AG La Esperanza / AG LA ESQUINA DARGGI / AG LA DIOSA DE LA FORTUNA / AG LA MONTA ITA  COPA DE ORO |
| AG-114 | 21, 666 | Loterías El Terminal / Loter as El Terminal |
| AG-215 | 108, 669 | 2776 / Ag Jose Cordero |
| AG-325 | 218, 670 | 4594 / AG LA LAJA |
| AG-369 | 262, 671 | PATIÑO / PATI O |
| AG-373 | 266, 672 | Ag Wilson / 6541 |
| AG-686 | 577, 673 | AG 4939 / 4939 |
| AG-181 | 622, 675 | AG. Yolanda / 4117 |
| 4994 | 631, 676 | AG DANY / AG NANY |
| AG-557 | 660, 677 | 5936 / Ag Consuelo |
| AG-681 | 663, 679 | 4934 / Ag Fruteria Santa Elena |
| PRUEBA | 674, 678 | PRUEBA / PRUEBA ORLANDO |

## 2. Equipos duplicados por serial

10 grupos tienen seriales iguales al quitar espacios y símbolos; cada grupo tiene dos filas. Un par también comparte el ID del equipo. Algunas parejas muestran estados distintos.

| Serial normalizado | Variantes del serial y estado |
|---|---|
| LR03PVZJ | LR 03PVZJ — Asignado / LR03PVZJ — En Depósito |
| LR03PQ8C | LR 03PQ8C — En Depósito / LR03PQ8C — En Depósito |
| LR03N5EC | LR 03N5EC — En Depósito / LR03N5EC — En Depósito |
| LR03PPN3 | LR 03PPN3 — En Depósito / LR03PPN3 — En Depósito |
| LR03PVN8 | LR 03PVN8 — En Depósito / LR03PVN8 — En Depósito |
| LR03PS0P | LR 03PS0P — En Depósito / LR03PS0P — En Depósito |
| LR03AMJG | LR 03AMJG — En Depósito / LR03AMJG — En Depósito |
| LR03PPZ6 | LR 03PPZ6 — En Depósito / LR03PPZ6 — En Depósito |
| LR03WCAJ | LR 03WCAJ — En Depósito / LR03WCAJ — En Depósito |
| LR03QRZU | LR 03QRZU — En Depósito / LR03QRZU — Asignado |

## 3. Asignaciones sin agencia en el catálogo

8 equipos asignados hacen referencia a nombres que no existen en el catálogo. Esto no demuestra que las agencias no existan físicamente: pueden haberse renombrado o eliminado del catálogo. La app relaciona estas asignaciones por el nombre.

| Equipo/serial | Agencia registrada |
|---|---|
| 9728 | AG 4953 |
| 5CG5401PXT | AG 4953 |
| WHZWV0A1RIZL7M | AG 4953 |
| 9644 | 7992 |
| 11245N0319Z1ZLZF48928A | AG 4991 |
| 0GJJYR | AG 4992 |
| DXZBPL2 | 7992 |
| OGJJYR | 7992 |

## 4. Mantenimientos con relaciones incompletas

7 mantenimientos activos usan un `equipId` ausente del inventario. Sus seriales sí tienen una fila de equipo. La función `resolveMaintenance` busca por ID exacto; puede marcar el mantenimiento como cerrado sin cambiar el equipo, porque la búsqueda no encuentra el ID.

| Mantenimiento | Serial | ID vinculado ausente | ID del equipo encontrado por serial |
|---|---|---|
| MNT-9840 | 5L5TK72 | 1789756321593 | REC-5301_948 |
| MNT-6767 | 9NQL2F2 | 1789756628022 | REC-6959_139 |
| MNT-5592 | 783057 | 1789759553015 | REC-1612_359 |
| MNT-9635 | 2DVKD32 | 1789759947206 | REC-9401_534 |
| MNT-7412 | 2RR7RN2 | 1790344364198 | REC-5405_356 |
| MNT-4897 | H17MVD2 | 1790351550348 | REC-1995_208 |
| MNT-1171 | 4CZ1260R4Y | 1790354389872 | REC-5357_8 |

Además, 2 registros de taller activos corresponden a seriales que aparecen en depósito; 11 equipos marcados en mantenimiento no tienen registro de taller activo. Se deben revisar antes de cerrar o devolver esos equipos.

## 5. Diferencias entre inventario e historial

23 filas de inventario no coinciden con el estado que sugiere su último movimiento operativo encontrado por serial normalizado. Es una comparación del archivo, no una verificación del estado físico del equipo; no se corrigieron estos estados automáticamente.

| Serial | Estado en inventario | Estado sugerido | Fecha del movimiento | Movimiento |
|---|---|---|---|---|
| 3HBL662 | En Depósito | Asignado | 2026-10-05 09:34:04 | Salida Combo Kit |
| DELL 9497 | Asignado | En Depósito | 2026-10-05 15:30:51 | Reingreso / Devolución |
| 022504230903613 | En Depósito | Asignado | 2026-10-05 11:20:39 | Salida / Asignación |
| 022504230903652 | En Depósito | Asignado | 2026-10-06 11:37:45 | Salida / Asignación |
| LR 03QRZU | En Depósito | Asignado | 2026-09-30 11:06:55 | Salida Combo Kit |
| WCNXA0C3U3SC2J | Asignado | En Depósito | 2026-10-05 11:35:03 | Reingreso / Devolución |
| LR03PVZJ | En Depósito | Asignado | 2026-09-16 09:24 | Salida / Asignación |
| PRUEBA | En Depósito | Asignado | 2026-09-23 22:47:03 | Salida Combo Kit |
| 2081460 | Asignado | En Depósito | 2026-10-06 15:54:43 | Reingreso / Devolución |
| 9845 | En Depósito | Asignado | 2026-10-07 11:42:22 | Salida Combo Kit |
| 9847 | En Depósito | Asignado | 2026-10-07 11:42:22 | Salida Combo Kit |
| CN0HN6624789079KA1VS | En Depósito | Asignado | 2026-10-05 09:34:04 | Salida Combo Kit |
| CNU41694R3 | En Depósito | Asignado | 2026-10-07 11:35:28 | Salida Combo Kit |
| 0013512 | En Depósito | Asignado | 2026-10-05 12:26:43 | Salida Combo Kit |
| 9909 | En Depósito | Asignado | 2026-10-05 17:29:33 | Salida Combo Kit |
| 16PR2G2 | En Depósito | Asignado | 2026-10-06 12:27:54 | Salida Combo Kit |
| 9931 | En Depósito | Asignado | 2026-10-05 17:29:33 | Salida Combo Kit |
| 8SSD50L21284KT204E1N | En Depósito | Asignado | 2026-10-05 17:29:33 | Salida Combo Kit |
| CNC809RV7Y | En Depósito | Asignado | 2026-10-05 17:29:33 | Salida Combo Kit |
| 9946 | En Depósito | Asignado | 2026-10-05 10:20:08 | Salida / Asignación |
| MY19H9LQ522404H | En Depósito | Asignado | 2026-10-05 12:26:43 | Salida Combo Kit |
| 2UA4180NX0 | En Depósito | Asignado | 2026-10-05 12:26:43 | Salida Combo Kit |
| WJFXC0A1RIXK6N | En Depósito | Asignado | 2026-10-07 11:35:28 | Salida Combo Kit |

## 6. Resultado de simular la importación actual

Se ejecutaron las funciones reales del importador en memoria, sin navegador, sin Firebase y con los registros de eliminaciones inicialmente vacíos.

- Inventario: 979 → 969 filas; se descartan 10 filas duplicadas según la puntuación del importador.
- Agencias: se conservan 681 filas, pero se cambian 16 IDs y 9 códigos para evitar repeticiones. Los sufijos automáticos no resuelven si dos filas son agencias diferentes o versiones de la misma.
- Movimientos: se conservan 1.327 registros.
- No se descartaron agencias ni equipos por el historial de eliminaciones en esa simulación. Un equipo con eliminaciones locales previas o un catálogo remoto diferente puede producir otro resultado.

El detalle está en `AUDITORIA RESPALDO - SIMULACION.json`.

## 7. Firmas y datos nuevos

Se decodificaron las 290 firmas presentes: ninguna estaba dañada, pero las 290 tenían las cuatro esquinas negras (indicador de fondo negro). Los otros 1.037 movimientos no tienen firma. La corrección del guardado de firmas nuevas no cambia estas imágenes antiguas.

Ningún movimiento trae el campo nuevo `transactionType`; los tipos Compra/Venta/Asignación aparecerán como «Sin registrar». Eso es compatible y no impide importar. No se inventaron clasificaciones retrospectivas.

## 8. Tamaño y persistencia

El archivo ocupa 5.650.345 bytes. El historial compacto ocupa aproximadamente 4,78 MB de texto UTF-8. La app almacena el historial en localStorage: si el navegador alcanza su cuota, `safeSetLocalStorage` intenta conservar solo los últimos 40 movimientos. Es un riesgo de persistencia local; no se midió la cuota ni el espacio disponible en el navegador del usuario.

Las estimaciones de tamaño de los documentos actuales están muy por debajo del límite de 1 MiB por documento:

| Documento | Bytes estimados |
|---|---:|
| inventory | 249906 |
| agencies | 133399 |
| maintenanceLog | 12210 |
| largestMovement | 54342 |

[Límites oficiales de Firebase](https://firebase.google.com/docs/firestore/quotas) · [Cálculo oficial de tamaños](https://firebase.google.com/docs/firestore/storage-size). Estas comprobaciones no verifican permisos, cuota diaria ni la sincronización del proyecto real.

## Recomendación

Primero resolver las identidades de agencias, los 10 pares de equipos y las relaciones de mantenimiento. Después conciliar asignaciones y estados con el responsable del inventario. Mantener este archivo intacto y preparar una copia depurada, con una lista explícita de cambios.

Importar mediante «Respaldos → Importar respaldo JSON»; no publicar el JSON junto a los archivos de la web. La importación escribe movimientos por ID en Firebase y no borra los movimientos anteriores que existan en el servidor. Si la nube contiene otra información, el historial puede mezclarse; no equivale a una restauración completa de una base vacía.

No se concede luz verde para usar este archivo tal cual como fuente definitiva. La comprobación realizada es estática y una simulación local, sin modificar los datos reales.
