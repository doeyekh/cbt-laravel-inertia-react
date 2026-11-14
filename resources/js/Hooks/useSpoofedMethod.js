import { useEffect } from 'react';

/**
 * Kirim request yang membawa file sebagai POST dengan spoofing method.
 *
 * PHP hanya meng-parse body `multipart/form-data` pada request POST. Request
 * PUT/PATCH dengan body multipart akan tiba di server tanpa payload apa pun,
 * sehingga file dan field lain ikut hilang. Laravel menyediakan method
 * spoofing lewat field `_method` untuk menutup celah tersebut.
 *
 * @param {Function} transform  Transform dari `useForm`.
 * @param {boolean} isEdit      Apakah request ini untuk memperbarui data.
 * @param {string} method       Method tujuan, default `PUT`.
 */
export default function useSpoofedMethod(transform, isEdit, method = 'PUT') {
    useEffect(() => {
        transform((formData) =>
            isEdit ? { ...formData, _method: method } : formData
        );
    }, [isEdit, method, transform]);
}
