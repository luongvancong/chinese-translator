export function splitWithDelimiter(str, delimiter) {
    const parts = str.split(delimiter);
    const result = [];

    for (let i = 0; i < parts.length; i++) {
        result.push(parts[i]); // Thêm phần đã tách vào mảng
        if (i < parts.length - 1) {
            result.push(delimiter); // Thêm delimiter vào giữa các phần
        }
    }

    return result;
}
