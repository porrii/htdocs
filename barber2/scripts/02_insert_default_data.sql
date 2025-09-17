-- Insertar configuración por defecto
INSERT INTO config (smtp_host, smtp_port, smtp_username, smtp_password, appointment_interval, booking_restriction) 
VALUES ('smtp.gmail.com', 587, '', '', 60, 30);

-- Insertar servicios por defecto
INSERT INTO services (name, description, duration, price) VALUES
('Corte Clásico', 'Corte tradicional con tijeras y máquina, incluye lavado', 45, 15.00),
('Corte + Barba', 'Corte completo más arreglo de barba con navaja', 60, 25.00),
('Afeitado Tradicional', 'Afeitado completo con navaja y toallas calientes', 30, 18.00),
('Corte Moderno', 'Cortes actuales y tendencias, incluye peinado', 50, 20.00),
('Arreglo de Barba', 'Perfilado y arreglo de barba con productos premium', 25, 12.00);

-- Insertar herramientas/productos por defecto
INSERT INTO tools_products (name, description, type, brand) VALUES
('Máquina Wahl Professional', 'Máquina profesional para cortes precisos', 'tool', 'Wahl'),
('Navaja Dovo', 'Navaja alemana de acero inoxidable', 'tool', 'Dovo'),
('Pomada American Crew', 'Pomada para peinado con fijación media', 'product', 'American Crew'),
('Aceite para Barba Beardbrand', 'Aceite nutritivo para barba', 'product', 'Beardbrand'),
('Tijeras Jaguar', 'Tijeras profesionales alemanas', 'tool', 'Jaguar');

-- Insertar horarios de trabajo por defecto (Lunes a Sábado)
INSERT INTO work_schedules (day_of_week, is_working_day, morning_start, morning_end, afternoon_start, afternoon_end) VALUES
(0, FALSE, NULL, NULL, NULL, NULL), -- Domingo cerrado
(1, TRUE, '09:00:00', '13:00:00', '16:00:00', '20:00:00'), -- Lunes
(2, TRUE, '09:00:00', '13:00:00', '16:00:00', '20:00:00'), -- Martes
(3, TRUE, '09:00:00', '13:00:00', '16:00:00', '20:00:00'), -- Miércoles
(4, TRUE, '09:00:00', '13:00:00', '16:00:00', '20:00:00'), -- Jueves
(5, TRUE, '09:00:00', '13:00:00', '16:00:00', '20:00:00'), -- Viernes
(6, TRUE, '09:00:00', '14:00:00', NULL, NULL); -- Sábado solo mañana

-- Crear usuario administrador por defecto (password: admin123)
INSERT INTO admins (username, password_hash, email) 
VALUES ('admin', '$2y$10$KLOKWILaaB.pOPkL7Y98PumwaakR5CTZdtiixPkmXBt4M1cHDcbaq', 'admin@barbershop.com');
