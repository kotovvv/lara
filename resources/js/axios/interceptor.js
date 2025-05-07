import axios from "axios";

axios.interceptors.response.use(
  (response) => {
    // Возвращаем ответ, если статус успешный
    return response;
  },
  (error) => {
    // Обрабатываем статус 419
    if (error.response && error.response.status === 419) {
      document.getElementById("logout").click();
    }
    // Пробрасываем ошибку дальше
    return Promise.reject(error);
  }
);

export default axios;
