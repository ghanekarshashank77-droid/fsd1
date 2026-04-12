const express = require('express');
const authController = require('../controllers/authController');

const router = express.Router();

router.post('/register', authController.register);
router.post('/login', authController.login);
router.get('/me', authController.protect, authController.getMe);

// CRUD
router.get('/students', authController.protect, authController.getAllStudents);
router.patch('/students/:id', authController.protect, authController.updateStudent);
router.delete('/students/:id', authController.protect, authController.deleteStudent);

module.exports = router;
