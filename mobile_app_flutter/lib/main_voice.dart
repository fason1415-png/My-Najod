import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:http/http.dart' as http;
import 'package:speech_to_text/speech_to_text.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const NajotLinkVoiceApp());
}

class NajotLinkVoiceApp extends StatelessWidget {
  const NajotLinkVoiceApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'MY NajotLink Mobile',
      theme: ThemeData.dark(useMaterial3: true),
      home: const VoiceSosScreen(),
    );
  }
}

class VoiceSosScreen extends StatefulWidget {
  const VoiceSosScreen({super.key});

  @override
  State<VoiceSosScreen> createState() => _VoiceSosScreenState();
}

class _VoiceSosScreenState extends State<VoiceSosScreen> {
  final SpeechToText _speech = SpeechToText();
  final TextEditingController _apiCtrl = TextEditingController(text: 'http://40.47.0.7/dashboard/06');

  bool _listening = false;
  bool _sending = false;
  String _status = 'SOS tugmani bosing va gapiring';
  String _spoken = '';
  double? _lat;
  double? _lng;
  String _result = '';

  @override
  void dispose() {
    _apiCtrl.dispose();
    super.dispose();
  }

  Future<void> _onSosPressed() async {
    if (_sending) return;
    if (!_listening) {
      await _startListening();
    } else {
      await _stopAndSend();
    }
  }

  Future<void> _startListening() async {
    final ready = await _speech.initialize(
      onError: (e) {
        if (!mounted) return;
        setState(() {
          _listening = false;
          _status = 'Speech xatolik: ${e.errorMsg}';
        });
      },
      onStatus: (s) {
        if (!mounted) return;
        if (s == 'done') {
          setState(() => _listening = false);
        }
      },
    );

    if (!ready) {
      setState(() => _status = 'Mikrofon ruxsati yoq yoki speech service topilmadi');
      return;
    }

    String locale = 'uz_UZ';
    try {
      final locales = await _speech.locales();
      final uz = locales.where((l) => l.localeId.toLowerCase().startsWith('uz')).toList();
      if (uz.isNotEmpty) {
        locale = uz.first.localeId;
      } else if (locales.isNotEmpty) {
        locale = locales.first.localeId;
      }
    } catch (_) {}

    setState(() {
      _listening = true;
      _spoken = '';
      _status = 'Tinglanmoqda... gapiring';
      _result = '';
    });

    await _speech.listen(
      localeId: locale,
      partialResults: true,
      listenFor: const Duration(seconds: 25),
      pauseFor: const Duration(seconds: 4),
      onResult: (res) {
        if (!mounted) return;
        setState(() {
          _spoken = res.recognizedWords.trim();
          _status = 'Ovoz matnga olinmoqda...';
        });
      },
    );
  }

  Future<void> _stopAndSend() async {
    await _speech.stop();
    setState(() => _listening = false);

    if (_spoken.trim().isEmpty) {
      setState(() => _status = 'Shikoyat eshitilmadi. Qayta urinib ko‘ring.');
      return;
    }

    setState(() {
      _sending = true;
      _status = 'GPS olinmoqda...';
    });

    try {
      final pos = await _getLocation();
      _lat = pos.latitude;
      _lng = pos.longitude;

      setState(() => _status = 'SOS serverga yuborilmoqda...');

      final data = await _sendToServer(
        baseUrl: _apiCtrl.text.trim(),
        voiceText: _spoken,
        latitude: pos.latitude,
        longitude: pos.longitude,
      );

      setState(() {
        _status = 'Yuborildi: ${data['alert']?['service'] ?? '-'}';
        _result = jsonEncode(data);
      });
    } catch (e) {
      setState(() => _status = 'Xatolik: $e');
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  Future<Position> _getLocation() async {
    final enabled = await Geolocator.isLocationServiceEnabled();
    if (!enabled) throw Exception('GPS yoqilmagan');

    var perm = await Geolocator.checkPermission();
    if (perm == LocationPermission.denied) {
      perm = await Geolocator.requestPermission();
    }

    if (perm == LocationPermission.denied || perm == LocationPermission.deniedForever) {
      throw Exception('Lokatsiya ruxsati berilmagan');
    }

    return Geolocator.getCurrentPosition(desiredAccuracy: LocationAccuracy.high);
  }

  Future<Map<String, dynamic>> _sendToServer({
    required String baseUrl,
    required String voiceText,
    required double latitude,
    required double longitude,
  }) async {
    if (baseUrl.isEmpty) {
      throw Exception('API URL bo‘sh');
    }

    final uri = Uri.parse('$baseUrl/sos-create.php');
    final response = await http.post(
      uri,
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({
        'user_id': 'USR-001',
        'device_id': 'DEV-001',
        'voice_text': voiceText,
        'latitude': latitude,
        'longitude': longitude,
      }),
    );

    final body = jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode < 200 || response.statusCode >= 300 || body['ok'] != true) {
      throw Exception(body['message']?.toString() ?? 'Server xatoligi');
    }
    return body;
  }

  @override
  Widget build(BuildContext context) {
    final buttonText = _sending
        ? 'Yuborilmoqda...'
        : (_listening ? 'Yuborish uchun qayta bosing' : 'SOS');

    return Scaffold(
      appBar: AppBar(title: const Text('MY NajotLink Mobile')),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: ListView(
          children: [
            TextField(
              controller: _apiCtrl,
              decoration: const InputDecoration(
                labelText: 'API URL',
                hintText: 'http://40.47.0.7/dashboard/06',
              ),
            ),
            const SizedBox(height: 12),
            Container(
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: Colors.white24),
              ),
              padding: const EdgeInsets.all(12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Holat: $_status'),
                  const SizedBox(height: 8),
                  Text('Matn: ${_spoken.isEmpty ? '-' : _spoken}'),
                  const SizedBox(height: 8),
                  Text('GPS: ${_lat == null ? '-' : '${_lat!.toStringAsFixed(6)}, ${_lng!.toStringAsFixed(6)}'}'),
                ],
              ),
            ),
            const SizedBox(height: 18),
            Center(
              child: GestureDetector(
                onTap: _onSosPressed,
                child: Container(
                  width: 220,
                  height: 220,
                  decoration: const BoxDecoration(
                    shape: BoxShape.circle,
                    gradient: RadialGradient(
                      colors: [Color(0xFFFF9AB1), Color(0xFFFF4D6D), Color(0xFF7A0820)],
                    ),
                    boxShadow: [
                      BoxShadow(color: Color(0x66FF4D6D), blurRadius: 40, spreadRadius: 8),
                    ],
                  ),
                  child: Center(
                    child: Text(
                      buttonText,
                      textAlign: TextAlign.center,
                      style: const TextStyle(fontSize: 34, fontWeight: FontWeight.w800),
                    ),
                  ),
                ),
              ),
            ),
            const SizedBox(height: 16),
            if (_result.isNotEmpty)
              Container(
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: Colors.white24),
                ),
                padding: const EdgeInsets.all(12),
                child: Text('Server javobi: $_result'),
              ),
          ],
        ),
      ),
    );
  }
}
