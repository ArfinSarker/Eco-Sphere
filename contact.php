<?php
$page_title = "Contact Us";
include 'includes/header.php';

$database = new Database();
$db = $database->getConnection();

if($_POST) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    // Validate inputs
    $errors = [];
    
    if(empty($name)) $errors[] = "Name is required";
    if(empty($email)) $errors[] = "Email is required";
    if(empty($subject)) $errors[] = "Subject is required";
    if(empty($message)) $errors[] = "Message is required";
    
    if(empty($errors)) {
        $insert_query = "INSERT INTO inquiries (user_id, name, email, subject, message) 
                         VALUES (?, ?, ?, ?, ?)";
        $insert_stmt = $db->prepare($insert_query);
        
        if($insert_stmt->execute([$user_id, $name, $email, $subject, $message])) {
            $success = "Thank you for your message! We'll get back to you within 24 hours.";
            
            // Clear form fields
            $name = $email = $subject = $message = '';
        } else {
            $errors[] = "Something went wrong. Please try again.";
        }
    }
}
?>

<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>Contact Us</h2>
            <p>Get in touch with our plant experts</p>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem;">
            <!-- Contact Form -->
            <div>
                <div class="form-container" style="margin: 0;">
                    <h3>Send us a Message</h3>
                    
                    <?php if(isset($success)): ?>
                        <div class="alert alert-success">
                            <p><?php echo $success; ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if(isset($errors) && !empty($errors)): ?>
                        <div class="alert alert-error">
                            <?php foreach($errors as $error): ?>
                                <p><?php echo $error; ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post">
                        <div class="form-group">
                            <label for="name">Your Name *</label>
                            <input type="text" id="name" name="name" class="form-control" 
                                   value="<?php echo isset($name) ? $name : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Your Email *</label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   value="<?php echo isset($email) ? $email : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="subject">Subject *</label>
                            <input type="text" id="subject" name="subject" class="form-control" 
                                   value="<?php echo isset($subject) ? $subject : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="message">Your Message *</label>
                            <textarea id="message" name="message" class="form-control" rows="5" required><?php echo isset($message) ? $message : ''; ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn">Send Message</button>
                    </form>
                </div>
            </div>
            
            <!-- Contact Information -->
            <div>
                <div style="background: var(--light-green); padding: 2rem; border-radius: 8px; height: 100%;">
                    <h3 style="margin-bottom: 1.5rem;">Get in Touch</h3>
                    
                    <div style="margin-bottom: 2rem;">
                        <h4 style="color: var(--primary-green); margin-bottom: 0.5rem;">Visit Our Nursery</h4>
                        <p>
                            <i class="fas fa-map-marker-alt" style="color: var(--primary-green); margin-right: 0.5rem;"></i>
                            123 Green Street<br>
                            Eco City, EC 12345
                        </p>
                    </div>
                    
                    <div style="margin-bottom: 2rem;">
                        <h4 style="color: var(--primary-green); margin-bottom: 0.5rem;">Contact Information</h4>
                        <p>
                            <i class="fas fa-phone" style="color: var(--primary-green); margin-right: 0.5rem;"></i>
                            +1 (234) 567-8900<br>
                            <i class="fas fa-envelope" style="color: var(--primary-green); margin-right: 0.5rem;"></i>
                            info@ecosphere.com
                        </p>
                    </div>
                    
                    <div style="margin-bottom: 2rem;">
                        <h4 style="color: var(--primary-green); margin-bottom: 0.5rem;">Business Hours</h4>
                        <p>
                            Monday - Friday: 9:00 AM - 6:00 PM<br>
                            Saturday: 10:00 AM - 4:00 PM<br>
                            Sunday: Closed
                        </p>
                    </div>
                    
                    <div>
                        <h4 style="color: var(--primary-green); margin-bottom: 0.5rem;">Follow Us</h4>
                        <div class="social-icons">
                            <a href="https://www.facebook.com/rsrakibulhasan.rakib.79"><i class="fab fa-facebook-f"></i></a>
                            <a href="https://www.facebook.com/rsrakibulhasan.rakib.79"><i class="fab fa-instagram"></i></a>
                            <a href="https://www.facebook.com/rsrakibulhasan.rakib.79"><i class="fab fa-twitter"></i></a>
                            <a href="https://www.facebook.com/rsrakibulhasan.rakib.79"><i class="fab fa-pinterest"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Map Section -->
        <div style="margin-top: 4rem;">
            <h3 style="text-align: center; margin-bottom: 2rem;">Find Our Nursery</h3>
            <div style="background: var(--light-gray); height: 400px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                <div style="text-align: center;">
                    <i class="fas fa-map-marked-alt" style="font-size: 3rem; color: var(--primary-green); margin-bottom: 1rem;"></i>
                    <h4>Interactive Map</h4>
                    <p>Google Maps integration would go here</p>
                    <p style="font-size: 0.9rem; color: var(--text-color);">
                        123 Green Street, Eco City, EC 12345
                    </p>
                </div>
            </div>
        </div>
        
        <!-- FAQ Section -->
        <div style="margin-top: 4rem;">
            <h3 style="text-align: center; margin-bottom: 2rem;">Frequently Asked Questions</h3>
            <div style="display: grid; gap: 1rem;">
                <div style="background: var(--white); padding: 1.5rem; border-radius: 8px; box-shadow: var(--shadow);">
                    <h4 style="color: var(--primary-green);">Do you deliver plants?</h4>
                    <p>Yes, we offer safe and secure delivery for all our plants. We use specialized packaging to ensure your plants arrive in perfect condition.</p>
                </div>
                
                <div style="background: var(--white); padding: 1.5rem; border-radius: 8px; box-shadow: var(--shadow);">
                    <h4 style="color: var(--primary-green);">What if my plant arrives damaged?</h4>
                    <p>We have a 100% satisfaction guarantee. If your plant arrives damaged, contact us within 48 hours and we'll replace it or provide a full refund.</p>
                </div>
                
                <div style="background: var(--white); padding: 1.5rem; border-radius: 8px; box-shadow: var(--shadow);">
                    <h4 style="color: var(--primary-green);">Can I get advice on plant care?</h4>
                    <p>Absolutely! Our team of horticulture experts is always happy to provide personalized advice for your specific plants and growing conditions.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>